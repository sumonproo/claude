package main

import (
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"time"

	emailverifier "github.com/AfterShip/email-verifier"
)

// dohResult is a verification result computed without raw DNS (port 53),
// using DNS-over-HTTPS for the MX lookup. Useful where direct DNS is blocked.
type dohResult struct {
	Email      string `json:"email"`
	Status     string `json:"status"` // valid | invalid | unknown
	Reason     string `json:"reason,omitempty"`
	Syntax     bool   `json:"syntax"`
	HasMX      bool   `json:"has_mx"`
	Disposable bool   `json:"disposable"`
	RoleAcct   bool   `json:"role_account"`
	Free       bool   `json:"free"`
}

var httpClient = &http.Client{Timeout: 15 * time.Second}

type dohAnswer struct {
	Status int `json:"Status"`
	Answer []struct {
		Type int    `json:"type"`
		Data string `json:"data"`
	} `json:"Answer"`
}

func dohQuery(name, qtype string) (*dohAnswer, error) {
	endpoints := []string{"https://dns.google/resolve", "https://cloudflare-dns.com/dns-query"}
	var lastErr error
	for attempt := 0; attempt < 3; attempt++ {
		for _, ep := range endpoints {
			req, _ := http.NewRequest("GET", ep+"?name="+url.QueryEscape(name)+"&type="+qtype, nil)
			req.Header.Set("Accept", "application/dns-json")
			resp, err := httpClient.Do(req)
			if err != nil {
				lastErr = err
				continue
			}
			var a dohAnswer
			err = json.NewDecoder(resp.Body).Decode(&a)
			resp.Body.Close()
			if err != nil {
				lastErr = err
				continue
			}
			if a.Status == 2 { // SERVFAIL: try another resolver
				lastErr = fmt.Errorf("SERVFAIL")
				continue
			}
			return &a, nil
		}
		time.Sleep(time.Duration(attempt+1) * time.Second)
	}
	return nil, lastErr
}

// domainAcceptsMail reports whether the domain has a usable MX record
// (or, per RFC 5321, an A record when no MX exists). ok=false with err=nil
// means the domain definitely cannot receive mail.
func domainAcceptsMail(domain string) (ok bool, reason string, err error) {
	a, err := dohQuery(domain, "MX")
	if err != nil {
		return false, "", err
	}
	if a.Status == 3 {
		return false, "domain does not exist", nil
	}
	for _, ans := range a.Answer {
		if ans.Type != 15 {
			continue
		}
		f := strings.Fields(ans.Data)
		if len(f) == 2 && (f[1] == "." || f[1] == "") {
			return false, "null MX (domain accepts no mail)", nil
		}
		return true, "", nil
	}
	// No MX: fall back to an A record (implicit MX).
	a, err = dohQuery(domain, "A")
	if err != nil {
		return false, "", err
	}
	for _, ans := range a.Answer {
		if ans.Type == 1 {
			return true, "no MX, using A record", nil
		}
	}
	return false, "no mail server (no MX or A record)", nil
}

func verifyDoH(v *emailverifier.Verifier, email string) dohResult {
	r := dohResult{Email: email}
	syn := v.ParseAddress(email)
	r.Syntax = syn.Valid
	if !syn.Valid {
		r.Status, r.Reason = "invalid", "bad syntax"
		return r
	}
	r.Disposable = v.IsDisposable(syn.Domain)
	r.RoleAcct = v.IsRoleAccount(syn.Username)
	r.Free = v.IsFreeDomain(syn.Domain)
	if r.Disposable {
		r.Status, r.Reason = "invalid", "disposable domain"
		return r
	}
	ok, reason, err := domainAcceptsMail(syn.Domain)
	if err != nil {
		r.Status, r.Reason = "unknown", "dns lookup failed: "+err.Error()
		return r
	}
	r.HasMX, r.Reason = ok, reason
	if ok {
		r.Status = "valid"
	} else {
		r.Status = "invalid"
	}
	return r
}

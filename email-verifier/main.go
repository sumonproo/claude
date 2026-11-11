// Command email-verifier checks email addresses using AfterShip/email-verifier.
//
// Usage:
//
//	go run . someone@example.com other@example.org
//	go run . -smtp someone@example.com   # also probe the mail server (needs outbound port 25)
//	go run . -doh < emails.txt           # MX via DNS-over-HTTPS, one JSON line per address
package main

import (
	"bufio"
	"encoding/json"
	"flag"
	"fmt"
	"os"
	"strings"
	"sync"

	emailverifier "github.com/AfterShip/email-verifier"
)

func main() {
	smtp := flag.Bool("smtp", false, "enable SMTP mailbox check (requires outbound port 25)")
	doh := flag.Bool("doh", false, "check MX via DNS-over-HTTPS (for networks that block port 53); reads stdin if no args")
	flag.Parse()

	verifier := emailverifier.NewVerifier()
	if *doh {
		runDoH(verifier, flag.Args())
		return
	}

	if flag.NArg() == 0 {
		fmt.Fprintln(os.Stderr, "usage: email-verifier [-smtp|-doh] email [email...]")
		os.Exit(2)
	}

	if *smtp {
		verifier = verifier.EnableSMTPCheck()
	}

	enc := json.NewEncoder(os.Stdout)
	enc.SetIndent("", "  ")
	failed := false
	for _, email := range flag.Args() {
		res, err := verifier.Verify(email)
		if err != nil {
			fmt.Fprintf(os.Stderr, "%s: %v\n", email, err)
			failed = true
			continue
		}
		_ = enc.Encode(res)
	}
	if failed {
		os.Exit(1)
	}
}

func runDoH(v *emailverifier.Verifier, emails []string) {
	if len(emails) == 0 {
		sc := bufio.NewScanner(os.Stdin)
		for sc.Scan() {
			if e := strings.TrimSpace(sc.Text()); e != "" {
				emails = append(emails, e)
			}
		}
	}
	results := make([]dohResult, len(emails))
	sem := make(chan struct{}, 16)
	var wg sync.WaitGroup
	for i, e := range emails {
		wg.Add(1)
		sem <- struct{}{}
		go func(i int, e string) {
			defer wg.Done()
			defer func() { <-sem }()
			results[i] = verifyDoH(v, e)
		}(i, e)
	}
	wg.Wait()
	enc := json.NewEncoder(os.Stdout)
	for _, r := range results {
		_ = enc.Encode(r)
	}
}

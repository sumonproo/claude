// Command email-verifier checks email addresses using AfterShip/email-verifier.
//
// Usage:
//
//	go run . someone@example.com other@example.org
//	go run . -smtp someone@example.com   # also probe the mail server (needs outbound port 25)
package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"os"

	emailverifier "github.com/AfterShip/email-verifier"
)

func main() {
	smtp := flag.Bool("smtp", false, "enable SMTP mailbox check (requires outbound port 25)")
	flag.Parse()

	if flag.NArg() == 0 {
		fmt.Fprintln(os.Stderr, "usage: email-verifier [-smtp] email [email...]")
		os.Exit(2)
	}

	verifier := emailverifier.NewVerifier()
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

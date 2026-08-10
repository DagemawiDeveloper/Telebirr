# Security Policy

## Credential handling

Never commit any of the following to this repository:

- merchant application secrets
- merchant RSA private keys
- production callback secrets
- production API tokens
- customer/payment credentials

The integration expects credentials to be supplied through environment variables and expects the RSA private key to be mounted outside the repository.

## Historical credential exposure

Older revisions of this repository contained development/merchant credential material in tracked files. Deleting or replacing a secret in the current branch does **not** remove it from Git history.

Any credential or private key that has ever been committed should be considered exposed and must be revoked/rotated with the payment provider before it is used again.

If complete historical removal is required, rewrite repository history using an appropriate tool such as `git filter-repo` and coordinate any required force-push with repository users. Rotation is still required even after history is rewritten.

## TLS

Gateway requests must verify both the TLS certificate chain and hostname. Do not disable `CURLOPT_SSL_VERIFYPEER` or `CURLOPT_SSL_VERIFYHOST` to work around development certificate problems. Install the correct CA certificate instead.

## Payment callbacks

A production callback endpoint should:

1. Authenticate/verify the provider notification according to the current provider specification.
2. Reject malformed requests.
3. Use a persistent provider event/order identifier for idempotency.
4. Perform payment-state changes transactionally.
5. Return a deterministic acknowledgement.
6. Never log secrets or full sensitive payloads.

## Private key permissions

The file referenced by `TELEBIRR_PRIVATE_KEY_PATH` should be readable only by the application process. On Linux, use restrictive filesystem permissions and prefer a secret-manager mount in production.

## Reporting

Do not open a public issue containing credentials, private keys or exploitable payment details. Rotate compromised credentials first, then report the issue privately to the repository owner.

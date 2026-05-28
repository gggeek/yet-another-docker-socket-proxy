- Proxy
  - matching requests/responses
    - finish setting up and/or/not machers
    - todos in Rule class
	- urls: accommodate the /vXXX/ prefix transparently
	- urls: accommodate ``?aaa` and ``#bbb` transparently by default
	  Test the limits of what docker server answers on its own
	    version?a#b/c no
	    version?a#b yes
	    version/c no
	- urls: query string
	- req. body: literal, wildcard, regexp, jq-like matching
    - document the wildcard matching format
    - also, support other wildcards besides the ``*`?
      - glob has: ? for one char, [...] for char ranges, [!...] for negated char ranges
      - sql LIKE has `%` and `_`
      - we could just allow full regexp instead, at least for char ranges...
	- docker tags (how ??)
    - client
      - support v6 IPs
    - user-specified http headers
    - other? eg. ssl on
    - review: GET /containers/{id}/attach/ws allows to attach to a container stdin ? Is that RO ?
    - review the haproxy rules in NC-AIO haproxy.cfg
  - finish and test filtering setup
  - create a flow diagram with req/resp matching and filtering
  - test: support for `tcp:/` sockets
  - look at all cases mentioned at https://hackviser.com/tactics/pentesting/services/docker
  - allow injecting the logger to matchers
  - tls support

- add config examples and/or a config generator for common usecases, eg. 'all readonly', 'redact secrets', etc...

- Loggers
  - improve message formatting: add context

- proxy.php
  - catch exceptions, log them and return valid json?
    - test what happens with frankenphp by default with log_errors=on, error_Log=null -> one line per exception in the FP log
    - what about when in worker mode?

- test worker mode, incl. exceptions

- make favicon background transparent (or rounded)

- container build
  - run it on gha

- single-executable build

- add a testsuite
  - set up docker, make it listen on a tcp port; set up yadsp on a separate port
  - test differences in responses to both docker cli commands and synthetic requests
  - run it on gha
  - look for existing test suites, ex for https://github.com/fussybeaver/bollard, https://github.com/denibertovic/docker-hs,
    https://github.com/clue/reactphp-docker, https://github.com/moby/moby/tree/master/client

- allow fine-tuning resource usage? timeouts, maxconn, etc... (It's probably already possible via Caddy env vars. Test it...)
- allow end users to inject
  - matchers
  - filters
  via env vars + mounting dirs from the host

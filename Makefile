# print help -
help: ## List available targets (this page)
	@awk 'BEGIN {FS = ":.*?## "} /^[0-9a-zA-Z_-]+:.*?## / {printf "\033[36m%-45s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

composer-install: ## Check code style
	docker exec http-client /bin/sh -c 'composer install'

cs: ## Check code style
	docker exec http-client /bin/sh -c 'bin/cs'

cbf: ## Fix code style
	docker exec http-client /bin/sh -c 'bin/cbf'

stan: ## Fix code style
	docker exec http-client /bin/sh -c 'bin/stan'

test: ## Run tests
	docker exec http-client /bin/sh -c 'bin/tests'

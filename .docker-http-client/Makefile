help: ## list available targets (this page)
	@awk 'BEGIN {FS = ":.*?## "} /^[0-9a-zA-Z_-]+:.*?## / {printf "\033[36m%-45s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

dc-up: ## run all containers in background
	docker-compose up -d --build

dc-up-front: ## run all containers in foreground
	docker-compose up --build

dc-down: ## stop all containers
	docker-compose down

dc-rebuild: ## rebuild containers
	make dc-down
	make dc-up

d-ls: ## show all running containers
	docker container ls

d-cleanup: ## clear docker storage from all stopped containers
	docker system prune -a
	docker volume prune

d-unfuck: ## stop, delete all and return to factory state
	make dc-down
	make d-cleanup
	make dc-up

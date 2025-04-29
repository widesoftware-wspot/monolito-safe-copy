help:
	@echo -e '\n'
	@echo Variables:
	@echo -e '\t' IMAGE_NAME'  ': Nome da imagem docker usado em \'build-docker-push\'
	@echo -e '\t' TAG'         ': Tag de versão da imagem docker usado em \'build-docker-push\'
	@echo -e '\t' build-docker'      ': Cria o binário GO na pasta bin/ e imagem docker usando as variáveis 'IMAGE_NAME':'TAG'
	@echo -e '\t' build-docker-push' ': Cria o binário GO na pasta bin/, aimagem docker usando as variáveis 'IMAGE_NAME':'TAG' e faz push pro Registry
	@echo Examples:

	@echo -e '\t' \# TAG=1.0.0 IMAGE_NAME=mydocker make build-docker-push
	@echo -e '\n'

build-app:
	docker run --env-file .env-example --rm -v "${PWD}:/sites/wspot.com.br/" sa-saopaulo-1.ocir.io/grtenqmoni5x/wspot-php5.6-container-image:v1.0.18 php composer.phar install

	docker run --env-file .env-example --rm -v "${PWD}:/sites/wspot.com.br/" sa-saopaulo-1.ocir.io/grtenqmoni5x/wspot-php5.6-container-image:v1.0.18 npm --prefix src/Wideti/AdminBundle/Resources/public/frontend/js/first-config link typescript
	docker run --env-file .env-example --rm -v "${PWD}:/sites/wspot.com.br/" sa-saopaulo-1.ocir.io/grtenqmoni5x/wspot-php5.6-container-image:v1.0.18 npm --prefix src/Wideti/AdminBundle/Resources/public/frontend/js/first-config install jquery

	docker run --env-file .env-example --rm -v "${PWD}:/sites/wspot.com.br/" sa-saopaulo-1.ocir.io/grtenqmoni5x/wspot-php5.6-container-image:v1.0.18 php app/console assets:install --symlink --relative
	sudo chmod -R 775 *
	sudo rm -rf app/cache && mkdir -p app/cache
	sudo chmod -R 777 app/logs app/cache
	sudo rm -rf .git .cache/ .composer/ app/logs/* app/cache/* composer* phpcs.phar phpunit.phar*

build-docker: build-app
ifndef TAG
	$(error TAG is not set)
endif

ifndef IMAGE_NAME
	$(error IMAGE_NAME is not set)
endif
	docker build . -t sa-saopaulo-1.ocir.io/grtenqmoni5x/$(IMAGE_NAME):$(TAG)

build-docker-push: build-docker
ifndef TAG
	$(error TAG is not set)
endif

ifndef IMAGE_NAME
	$(error IMAGE_NAME is not set)
endif
	docker push sa-saopaulo-1.ocir.io/grtenqmoni5x/$(IMAGE_NAME):$(TAG)

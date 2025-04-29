
# Documentação API

Este repositório contém a documentação da API do WSpot.

## Pré requisito

 - Docker

## Arquivos

### doc.yaml
Este arquivo possui o código da API no padrão Open API 3, quando precisar efetuar alguma alteração, é neste arquivo que devemos efetuar.

### index.html
Esse é o arquivo renderizado a partir da especificação do arquivo doc.yaml. Ele já contém todo CSS e Javascript dentro e é o arquivo que será enviado para o Github Pages.

## Como gerar o arquivo renderizado
Para gerar o arquivo index.html que será utilizado no Github Pages, basta executar o comando abaixo:

```bash
cd docs
docker run --rm -i yousan/swagger-yaml-to-html < doc.yaml > index.html
```

> Não esqueça de executar o comando acima antes de fazer o push da branch. Caso contrário o arquivo não será atualizado no passo abaixo.

## Hospedagem do arquivo index.html
A hospedagem está sendo feita pelo Github Pages, no próprio repositório
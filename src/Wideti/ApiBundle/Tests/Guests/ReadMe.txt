Passos para executar os testes:

### Instalar Newman (cliente de testes Postman para o terminal) https://www.npmjs.com/package/newman
1) npm install newman --global;

### Executar os testes:
2) newman run api_postman_test.json -g postman_environment_variables.json

--------------------------------------------------------
Arquivo: api_postman_test.json
	São os testes nos end-points

Arquivo : postman_environment_variables.json
	São as variáveis de ambiente para o servidor Pré-Prod

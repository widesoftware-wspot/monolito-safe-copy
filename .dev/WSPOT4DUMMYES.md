 
# WSpot 4 Dummyes
Aqui você encontra as receitas para as principais funções do WSpot.

## Table of Contents 
[Conectar no banco local](#localDatabase)

[Acessando seu primeiro Painel](#primeiroPainel)

[Gerando sua primeira versão](#versaoTeste)

[Criando suas migrations](#migrations)

[Executando suas migrations](#migration)

[Exportação de relatórios](#reports)

[Simular login com 1 click](#login1click)

<a name="migrations"/></a>
## Criar uma migration
1. Primeiro vc precisa criar a migration
   docker exec -it wspot php5.6 app/console doctrine:migrations:generate
2. O arquivo da Migration será criada na pasta: app/migrations/VersionXXXXX.php
3. Selecione o arquivo que foi criado onde XXXX é o numero da versão da migration
4. Coloque o código como o exemplo abaixo:
   ```php
   class Version20200817124712 extends AbstractMigration
   {
   /**
    * @param Schema $schema
      */
      public function up(Schema $schema)
      {
      $this->addSql("CREATE TABLE sms_credit_historic (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, quantity INT DEFAULT NULL, operation VARCHAR(15) DEFAULT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
      $this->addSql("CREATE TABLE sms_credit (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, total_available INT DEFAULT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
      $this->addSql("INSERT INTO modules (`shortcode`, `name`) VALUES ('sms_marketing', 'SMS Marketing');");
      }
      /**
    * @param Schema $schema
      */
      public function down(Schema $schema)
      {
      $this->addSql("DROP TABLE sms_credit_historic ");
      $this->addSql("DROP TABLE sms_credit ");
      $this->addSql("DELETE FROM modules WHERE `shortcode` = 'sms_marketing'");
      }
      }
   ```
5. Para executar local
   ```shell
    docker exec -it wspot php5.6 app/console doctrine:migrations:execute --up XXXXX
   ```
   <i>XXXXX é a versão da Migration, caso o arquivo seja Version12345 o XXXX é 12345</i>
   
6. Para executar em produção é necessário que seja executado dentro de um POD:
   ```bash
   php5.6 app/console doctrine:migrations:execute --up --env prod XXXXX
   php5.6 app/console doctrine:migrations:execute --up --env prod --up 20210719163731
   ```


<a name="localDatabase"/></a>
## Para acessar o banco de dev local:

* <b>server host</b>: localhost:3307
* <b>usuário</b>: root
* <b>senha</b>: a1b2c3
* <b>database</b>: radius_wspotv3


<a name="primeiroPainel"/></a>
## Como acessar seu primeiro painel
* Alterar o Hosts seu virtual host, criando um apontamento para dev.wspot. No caso de usuários
  linux /etc/hosts.
  ```bash
    127.0.0.1	dev.wspot.com.br
  ```
   Windows 10 - "C:\Windows\System32\drivers\etc\hosts"
   Linux - "/etc/hosts"
   Mac OS X - "/private/etc/hosts"
   
*  http://dev.wspot.com.br/app_dev.php/admin/
* <b>usuario</b>: contato@wideti.com.br
* <b>senha</b>: ws@oqp!123

<a name="versaoTeste"/></a>
## Como gerar uma versão de teste
1. Crie um release: GitHub > TAGs > Releases > Draft a new Release > Escolha sua branch
  <i>Para gerar uma tag de teste, escolha a última versão em produção e acrescente uma breve
   descrição da versão, exemplo: v1.0.1-2FactorAuthentication.</i>
2. Uma nova action será disparada (acompanhar na aba Actions do Github)
3. Uma vez processada com sucesso a imagem estará disponível conforme a tag informada.
4. Para subir a nova versão de teste:
5. Caso tenha migration essa precisará ser executada no ambiente de prod pois os bancos são compartilhados entre os ambientes.
6. Acessar o repositório wspot-mono-ops e alterar a tag da imagem no escopo desejado:
```bash
|__ wspot-monolito-v3
|   |__ wspot-monolito-v3
|       |__ scopes
|           |__ batch-reports
|               |-- kustomization.yaml
|           |__ canary
|               |-- kustomization.yaml
|           |__ dev
|               |-- kustomization.yaml
|           |__ omega
|               |-- kustomization.yaml
|           |__ prod
|               |__ kustomization.yaml
```

7. Definido o escopo é necessário criar o roteamento em wspot-monolito-v3/mesh/virtual-service.yml
```bash
|__ wspot-monolito-v3
|   |__ wspot-monolito-v3
|       |__ mesh
|           |__ virtual-service.yml
```
8. Solicita merge via Pull Request
9. Aprovado o pull request, prossiga para o merge
10.  Merge realizado com sucesso é hora de realizar o Sync com o Argo.

<a name="migration"/></a>
## Como Rodar Migration
1. Abrir um Pod com a versão deseja (pode ser acessado via k9s ou dashboard k8s*)
2. ```bash
   php5.6 app/console doctrine:migrations:execute --up --env prod <version_migration>
   ```
<i>*Dasboard K8s: https://dashboard.k8s.wide.software/#/login</i>

<a name="reports"/></a>
## Exportação de relatórios
Ao solicitar uma exportação de relatórios e a quantidade de registros ultrapassar 1Mil Registros, este será realizado via Batch.
O sistema insere uma mensagem no tópico <b>batch_reports</b> do SNS da AWS.  Esse tópico aciona o seguinte endpoint do sistema: 
 ```bash
   https://demo.wspot.com.br/admin/relatorios/batch
```

Para testar localmente, devemos simular a chamada no SNS, para isso realize a seguinte chamada via curl:

 ```bash
curl --location --request POST 'http://dev.wspot.com.br/app_dev.php/admin/relatorios/batch' \
--header 'x-amz-sns-message-type: Notification' \
--header 'Content-Type: application/json' \
--data-raw '{
    "Type": "Notification",
    "MessageId": "eba97982-88fa-509b-961b-21b5dd757fa2",
    "TopicArn": "arn:aws:sns:sa-east-1:769177069788:wspot_homolog_batch_reports",
    "Message": "access_historic|{\"maxReportLinesPoc\":null,\"skip\":0}|1|contato@wideti.com.br|Csv|utf-8|1",
    "Timestamp": "2021-11-20T10:39:11.617Z",
    "SignatureVersion": "1",
    "Signature": "n33+Qd7XgyFl7z+3mcM5umJMdmfHs4qUqKMQPA2rCpYWr9sni1y5m0P0Wt1rvk4yxRHlcfvmK4pY70Aamg4F0JMixIiLvodFol+8d72uo6CHi8aJfTfxrJV6/5YczW7WuXGiHyOPkJmLOWob46ju5vigSH7ZjDP1dpJaBRPDalahxP9ZR5yNUbpkaAgtq88Qin8kcoTlV63+05n2fhoyPHhPmlO8+x5XVPKKwBwUUjgZ2zunvJYouIDsR7rbURGaoGg97rsgl9cUPIruyWCmXJALoB6h1rXPkeQ7YqwP7LEHQugK4OEW6qOuwNAfJ7aoQNUra3EG6REs5g5N/OJe2w==",
    "SigningCertURL": "https://sns.sa-east-1.amazonaws.com/SimpleNotificationService-010a507c1833636cd94bdb98bd93083a.pem",
    "UnsubscribeURL": "https://sns.sa-east-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:sa-east-1:769177069788:wspot_homolog_batch_reports:617c104f-c10d-41ef-a1e4-57f76c8528c4"
}'
```

<a name="login1click"/></a>
## Simular login com 1 click no ambiente de desenvolvimento.
Para simular o login com um click siga os passos abaixo:

* Habilite o login com 1 click: https://mambowifi.com/login-com-um-clique-no-hotspot-wifi-do-seu-estabelecimento/
Atenção: Altere o Período para login com 1-click para Sempre.

* Realize um cadastro:
  http://dev.wspot.com.br/app_dev.php/mikrotik?mac=MAC-OBTIVO-PASSO-ANTERIOR&link-login-only=0&identity=11-11-11-11-11-11

* Depois de realizar o cadastro acesse a area administrativa (http://dev.wspot.com.br/app_dev.php/admin/login), clique em Menu Visitantes > Visitantes ; Cliquem em Detalhes do visitante cadastrado no passo anterior. 

* Link para simular localmente: 
  Aqui você vai utilizar o mac obtido no passo anterior:
  http://dev.wspot.com.br/app_dev.php/mikrotik?mac=MAC-OBTIVO-PASSO-ANTERIOR&link-login-only=0&identity=11-11-11-11-11-11

* Link simulando acesso Mikrotik completo
* https://dev.wspot.com.br/app_dev.php/mikrotik?identity=11-11-11-11-11-11&mac=BB-99-9B-47-61-AA&link-login-only=https://httpstatusdogs.com/200-ok&error=&host-ip=0.0.0.0&ip=0.0.0.0&login-by=teste&server-address=0.0.0.0&server-name=mikrotik&session-id=&link-orig=http://0.0.0.0/teste

# Autenticação com Ambiente RADIUS Local

O ambiente local utilizando o servidor RADIUS é complexo. Diversos serviços precisam funcionar em conjunto para que a autenticação seja realizada com sucesso. Esta documentação tem como objetivo instruir sobre o funcionamento desse sistema, bem como orientar na configuração e realização de testes, especialmente em casos de alterações mais delicadas.

---

## Pré-requisitos

Para começar, é necessário ter acesso aos seguintes repositórios:

- [`radius-authentication`](https://github.com/widesoftware-wspot/wspot-plat-radius-authentication)
- [`wspot-plat-auth-session`](https://github.com/widesoftware-wspot/wspot-plat-auth-session)
- [`wspot-plat-auth-auth`](https://github.com/widesoftware-wspot/wspot-plat-auth-auth)

Cada um desses repositórios contém documentações complementares que detalham o funcionamento de suas respectivas etapas no processo.

---

## Visão Geral do Funcionamento

### Comunicação entre o Servidor RADIUS e o Microsserviço `radius-authentication`

O servidor RADIUS utiliza o módulo REST para se comunicar com o microsserviço [`radius-authentication`](https://github.com/widesoftware-wspot/wspot-plat-radius-authentication). Este microsserviço é responsável por verificar as permissões de autenticação dos usuários. 

Quando um usuário tenta se autenticar:
1. O servidor RADIUS encaminha uma solicitação via HTTP para o `radius-authentication`.
2. O `radius-authentication` responde com um status code que indica a decisão de acesso:
   - **200 OK**: acesso permitido.
   - **404 Not Found**: acesso rejeitado.

### Validação de Sessão

Para determinar se a autenticação será aceita ou rejeitada, o `radius-authentication` consulta o microsserviço [`wspot-plat-auth-session`](https://github.com/widesoftware-wspot/wspot-plat-auth-session). Este serviço verifica se existe uma sessão no Redis associada ao dispositivo que está tentando se autenticar.

- **Sessão encontrada**:
  - O `wspot-plat-auth-session` retorna a sessão válida para o `radius-authentication`.
  - O `radius-authentication` libera a conexão do dispositivo, retornando **200 OK**.
  
- **Sessão não encontrada**:
  - O `radius-authentication` retorna erro **404 Not Found**.
  - O servidor RADIUS interpreta isso como um **Access-Reject** e repassa essa resposta ao Access Point (AP).
  - O AP redireciona o dispositivo para a tela de login do *captive portal*.

### Fluxo após Login no Captive Portal

1. Após o usuário criar uma conta ou realizar login, uma *policy* é gerada e salva em:
   - ElasticSearch;
   - OCI;
   - Cluster Redis utilizado pelo [`wspot-plat-auth-session`](https://github.com/widesoftware-wspot/wspot-plat-auth-session).

2. Com a sessão criada e salva:
   - O *captive portal* finaliza o processo de autenticação.
   - O usuário é redirecionado para o "endereço de redirect" configurado na plataforma.

3. O redirecionamento aciona uma nova solicitação de acesso (Access-Request) ao servidor RADIUS, que repete o fluxo descrito acima. Desta vez:
   - A sessão deverá ser encontrada no Redis.
   - O `radius-authentication` retornará **200 OK**, interpretado pelo servidor RADIUS como **Access-Accept**.

---

## Fluxo Resumido

1. **Usuário tenta autenticação:** AP → RADIUS → `radius-authentication`.
2. **Validação de sessão:** `radius-authentication` → `wspot-plat-auth-session`.
   - Sessão válida: Access-Accept.
   - Sessão inexistente: Access-Reject → Redirecionamento para o *captive portal*.
3. **Login ou cadastro no *captive portal*:**
   - Sessão criada e salva.
   - Redirecionamento configurado.
4. **Nova tentativa de autenticação:** Sessão encontrada → Access-Accept.

---

## Notas Importantes

- **Monitoramento de Logs:** Verifique os logs do servidor RADIUS e do `radius-authentication` para identificar problemas durante o processo de autenticação.
- **Ambiente de Testes:** Antes de aplicar mudanças em produção, realize testes em um ambiente seguro para garantir a estabilidade do sistema.
- **Atualização da Documentação:** As documentações dos repositórios mencionados devem ser consultadas regularmente para acompanhar alterações nos serviços.
---

Com estas instruções, espera-se facilitar a configuração e resolução de problemas no ambiente RADIUS.
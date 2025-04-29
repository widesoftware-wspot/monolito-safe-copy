# Ambiente de desenvolvimento

## Primeiros Passos
Todos os comandos necessários para subir o ambiente estão no Makefile.
Primeiro passo é executar o seguinte comando:
```bash
make start-env
make ambiente
```

Após executar os comando acima, adicione em seu hosts a entrada:
127.0.0.1 dev.wspot.com.br

Acesse o WSpot através do endereço: http://dev.wspot.com.br/app_dev.php

Explore o Makefile, ele possui todas as ações necessárias para o desenvolvimento


## Exemplo de como makefile para docker-compose antigo

```
make start-env -f Makefile-legacy
```

## Para configuração de vídeo na campanha

Não é possível configurar 100% da campanha de vídeo no ambiente de desenvolvimento causa da estrutura que é usada (SNS não consegue mandar o callback), com isso, para ter uma campanha de vídeo no ambiente de desenvolvimento, execute as 3 queries abaixo:

````
INSERT INTO radius_wspotv3.campaign
(id, client_id, template_id, name, start_date, end_date, ssid, status, bg_color, redirect_url, in_access_points, created, updated)
VALUES(1, 1, NULL, 'Teste Video Pos', '2023-06-01', '2030-06-30', NULL, 1, '#000000', 'https://uol.com.br', 0, '2023-06-22 10:52:15.0', '2023-06-22 10:54:49.0');

INSERT INTO radius_wspotv3.campaign_media_video
(id, campaign_id, client_id, step, url_mp4, url, orientation, bucket_id)
VALUES(1, 1, 1, 'pos', '//videos.wspot.com.br/a372716c-d981-48af-bc81-95e11a8ced45/mp4/campaign_fuzeto_pos_14591_Mp4_Avc_Aac_16x9_1920x1080p_24Hz_6Mbps_qvbr.mp4', '//videos.wspot.com.br/a372716c-d981-48af-bc81-95e11a8ced45/hls/campaign_fuzeto_pos_14591.m3u8', 'portrait', 'a372716c-d981-48af-bc81-95e11a8ced45');

INSERT INTO radius_wspotv3.campaign_hours
(id, campaign_id, start_time, end_time)
VALUES(1, 1, '00:00', '23:59');

```

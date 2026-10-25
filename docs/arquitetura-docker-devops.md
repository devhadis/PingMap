# PingMap — Arquitetura Docker e DevOps

## 1. Visão geral

O PingMap utiliza Docker Compose para executar uma
aplicação Laravel com cinco serviços:

- app: aplicação Laravel em PHP-FPM;
- nginx: servidor web e proxy FastCGI;
- db: banco de dados PostgreSQL;
- redis: cache e infraestrutura de filas;
- worker: processamento de tarefas Laravel em fila.

A infraestrutura está definida no arquivo `compose.yaml`.

## 2. Diagrama da arquitetura

~~~text
             Usuário local
                  |
          127.0.0.1:8080
                  |
                  v
                Nginx
                  |
             FastCGI
                  |
                  v
            Laravel / PHP
                  |
           +------+------+
           |             |
           v             v
       PostgreSQL       Redis

       Worker Laravel
           |      |
           v      v
      PostgreSQL Redis
~~~

O Nginx encaminha scripts PHP ao serviço `app`
na porta interna 9000.

O worker utiliza a mesma imagem PHP da aplicação,
mas executa um comando específico para processar filas.

## 3. Serviços Docker

### 3.1. Aplicação PHP

O serviço `app` é construído utilizando:

`docker/php/Dockerfile`

A imagem base é `php:8.4-fpm`.

Extensões PHP instaladas:

- pdo_pgsql;
- zip;
- intl;
- pcntl;
- bcmath.

Ferramentas de diagnóstico instaladas:

- ping;
- traceroute;
- MTR;
- utilitários DNS.

O Composer é disponibilizado a partir da imagem
oficial `composer:2`.

O código do projeto é montado em:

`/var/www/html`

### 3.2. Nginx

Imagem: `nginx:stable-alpine`

Porta publicada:

`127.0.0.1:8080:80`

A raiz web é:

`/var/www/html/public`

O Nginx utiliza:

`docker/nginx/default.conf`

As requisições PHP são encaminhadas ao serviço:

`app:9000`

O código da aplicação e a configuração Nginx são
montados somente para leitura nesse contêiner.

A configuração também bloqueia acesso HTTP a
arquivos ocultos, com exceção do caminho
`.well-known`.

### 3.3. PostgreSQL

Imagem: `postgres:17-alpine`

Banco de dados: `pingmap`

Usuário: `pingmap`

A senha é obtida da variável `DB_PASSWORD`.

O Compose exige que essa variável esteja definida.

O serviço utiliza o volume:

`postgres_data`

O healthcheck utiliza `pg_isready`.

### 3.4. Redis

Imagem: `redis:7-alpine`

O Redis é iniciado com:

`redis-server --appendonly yes`

Essa configuração habilita persistência AOF.

Os dados utilizam o volume:

`redis_data`

O healthcheck executa:

`redis-cli ping`

### 3.5. Worker Laravel

O serviço `worker` utiliza o mesmo Dockerfile
do serviço `app`.

Comando de execução:

~~~bash
php artisan queue:work --sleep=3 --tries=3 --timeout=100
~~~

Parâmetros:

- sleep=3: espera de 3 segundos entre verificações
  quando não há trabalho disponível;
- tries=3: até 3 tentativas de processamento;
- timeout=100: limite de 100 segundos por job.

O worker depende da disponibilidade inicial
dos serviços PostgreSQL e Redis.

## 4. Dependências e inicialização

Os serviços `app` e `worker` utilizam `depends_on`
com a condição `service_healthy` para PostgreSQL
e Redis.

O Nginx depende do início do serviço `app`.

Essa configuração organiza a inicialização,
mas não substitui verificações completas da
disponibilidade HTTP da aplicação.

Todos os serviços possuem a política:

`restart: unless-stopped`

## 5. Persistência de dados

O Docker Compose define dois volumes nomeados:

- postgres_data: arquivos do PostgreSQL;
- redis_data: dados persistidos pelo Redis.

Os volumes permanecem após a remoção normal
dos contêineres com `docker compose down`.

O comando `docker compose down -v` remove também
os volumes do projeto e pode causar perda de dados.

## 6. Segurança da infraestrutura

As configurações observadas incluem:

- Publicação HTTP restrita ao loopback do host;
- PostgreSQL sem porta publicada no host;
- Redis sem porta publicada no host;
- Senha PostgreSQL fornecida por variável;
- Código montado somente para leitura no Nginx;
- Bloqueio de acesso HTTP a arquivos ocultos;
- Healthchecks de PostgreSQL e Redis.

Essas medidas não representam, isoladamente,
uma configuração pronta para produção.

Uma implantação pública exige avaliação adicional
de HTTPS, segredos, permissões, backups,
observabilidade e segurança operacional.

## 7. Comandos operacionais

Validar a configuração:

~~~bash
docker compose config --quiet
~~~

Iniciar os serviços:

~~~bash
docker compose up -d --build
~~~

Verificar os contêineres:

~~~bash
docker compose ps
~~~

Visualizar logs recentes:

~~~bash
docker compose logs --tail=100
~~~

Visualizar logs do worker:

~~~bash
docker compose logs --tail=100 worker
~~~

Executar testes Laravel:

~~~bash
docker compose exec -T app php artisan test
~~~

Parar e remover os contêineres preservando
os volumes nomeados:

~~~bash
docker compose down
~~~

## 8. Arquivos de referência

- `compose.yaml`
- `docker/php/Dockerfile`
- `docker/nginx/default.conf`
- `.env.example`, quando disponível
- `README.md`

## 9. Evoluções recomendadas

Possíveis melhorias futuras:

- Pipeline CI/CD;
- Verificação automatizada de disponibilidade HTTP;
- Estratégia de backup e restauração;
- Monitoramento e métricas dos contêineres;
- Revisão de segurança para produção;
- Implantação com HTTPS.

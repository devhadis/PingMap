# PingMap

**Plataforma web de diagnóstico, análise e observabilidade de redes.**

O PingMap é um projeto desenvolvido com PHP, Laravel e Docker
para reunir ferramentas de diagnóstico de redes em uma única
interface web.

O objetivo é facilitar a análise de conectividade, roteamento,
resolução de nomes, segurança DNS e informações de endereçamento IP.

O projeto também demonstra práticas de desenvolvimento backend,
DevOps, segurança de aplicações e testes automatizados.

---

## Funcionalidades

| Ferramenta | Finalidade |
|---|---|
| Ping | Verificar conectividade e métricas de rede |
| Traceroute | Identificar os saltos até um destino |
| MTR | Analisar rotas e métricas dos saltos |
| TCP | Verificar conectividade em portas permitidas |
| DNS | Consultar registros DNS e resolução reversa |
| DNSSEC | Analisar a validação de segurança DNS |
| Email DNS | Analisar registros SPF, DKIM e DMARC |
| RDAP | Consultar informações de registro de endereços IP |
| GeoIP | Obter informações geográficas de IPs públicos |
| BGP Lookup | Consultar ASN, prefixos e informações de roteamento |

### BGP Lookup

A ferramenta BGP permite consultas por:

- ASN;
- IPv4 público;
- IPv6 público;
- Prefixos CIDR IPv4 e IPv6;
- Domínios públicos.

As consultas por rede utilizam dados da API RIPEstat.

Para consultas por domínio, a aplicação realiza resolução
DNS-over-HTTPS, identifica endereços públicos e consulta
informações BGP dos IPs encontrados.


### Integração RDAP + BGP

Após consultar um endereço IP público no RDAP, o usuário
pode consultar suas informações de roteamento BGP sem
sair da página.

A integração apresenta prefixo anunciado, ASN de origem
e estado do anúncio, mantendo os dados RDAP disponíveis.

O fluxo possui testes automatizados para tratamento de
falhas e respostas assíncronas antigas.

**Documentação técnica:**
[Integração RDAP + BGP](docs/etapa-112-rdap-bgp.md)

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.4 e Laravel 13 |
| Servidor web | Nginx |
| Banco de dados | PostgreSQL |
| Cache e filas | Redis |
| Infraestrutura | Docker e Docker Compose |
| Frontend | Blade, HTML, CSS e JavaScript |
| Testes | PHPUnit / Laravel Test |
| Versionamento | Git e GitHub |

---

## Arquitetura

O projeto utiliza uma arquitetura baseada em controllers,
serviços especializados e processamento assíncrono.

```text
             Usuário
                |
                v
         Interface Web
                |
                v
              Nginx
                |
                v
          Laravel / PHP
                |
       +--------+--------+
       |        |        |
       v        v        v
  PostgreSQL  Redis   Serviços
                       de rede
                          |
                          v
                    APIs externas

      Worker Laravel + Redis
       para tarefas em fila
```

Os componentes são executados em contêineres Docker,
facilitando o isolamento dos serviços e a manutenção
do ambiente de desenvolvimento.


### Serviços Docker

O ambiente de desenvolvimento utiliza cinco serviços
gerenciados pelo Docker Compose:

| Serviço | Responsabilidade |
|---|---|
| `app` | Aplicação Laravel com PHP 8.4-FPM |
| `nginx` | Servidor HTTP e encaminhamento FastCGI |
| `db` | Persistência de dados com PostgreSQL 17 |
| `redis` | Cache e infraestrutura de filas |
| `worker` | Processamento assíncrono de tarefas Laravel |

O PostgreSQL e o Redis utilizam volumes nomeados
para persistência de dados.

No ambiente Docker Compose atual, a aplicação é
publicada apenas no loopback do host, na porta 8080.

A infraestrutura inclui verificações de saúde
para PostgreSQL e Redis e políticas de reinicialização
dos contêineres.

**Documentação técnica:**
[Arquitetura Docker e DevOps](docs/arquitetura-docker-devops.md)

---

## Documentação das APIs

O PingMap possui 22 rotas de API para diagnósticos
e consultas de rede.

A referência reúne os contratos das 22 rotas
da API, incluindo autenticação, parâmetros,
exemplos JSON e códigos HTTP.

Consulte a [Referência das APIs](docs/api-referencia.md).

## Executando o projeto

> **IMPORTANTE:** as instrucoes de instalacao inicial
> destinam-se a uma copia nova do projeto.
>
> Em um ambiente ja configurado, nao substitua o
> arquivo `.env`, nao gere uma nova `APP_KEY` e nao
> remova os volumes do Docker.


### Pré-requisitos

- Git para clonar o repositório;
- Docker Engine e Docker Compose;
- Acesso à internet para baixar as imagens e dependências;
- Node.js 24, utilizado por meio de um contêiner Docker;
- Uma senha PostgreSQL definida no `.env`.

O PHP 8.4 e o Composer 2 são fornecidos pela imagem
Docker da aplicação. Não é necessário instalá-los
diretamente no computador.

O Nginx utiliza a porta `8080`, vinculada ao endereço
`127.0.0.1` da máquina que executa o Docker.

### Instalação nova: preparar o ambiente

Esta seção destina-se exclusivamente a uma cópia nova
do repositório. Não execute estas etapas em uma
instalação já configurada.

**1. Criar o arquivo de configuração**

Na pasta do projeto, execute:

```bash
test ! -e .env && cp .env.example .env
```

O comando somente copia o exemplo quando o arquivo
`.env` ainda não existe.

**2. Configurar o PostgreSQL**

Abra o arquivo:

```bash
nano .env
```

Mantenha as configurações PostgreSQL fornecidas pelo
`.env.example` e defina uma senha forte em
`DB_PASSWORD`.

Não publique o arquivo `.env` no GitHub.

**3. Validar a configuração e compilar as imagens**

```bash
docker compose config --quiet
docker compose build app worker
```

Esses comandos validam o Docker Compose e preparam
as imagens PHP 8.4 utilizadas pela aplicação.

**4. Instalar as dependências PHP**

Execute o Composer dentro do contêiner Docker:

```bash
docker compose run --rm --no-deps app composer install --prefer-dist --no-interaction --no-progress
```

O Composer utiliza o arquivo `composer.lock` para
instalar as dependências do Laravel.

**5. Gerar a chave da aplicação**

Em uma instalação nova, gere a `APP_KEY` somente
se ela ainda estiver vazia:

```bash
grep -q '^APP_KEY=$' .env && docker compose run --rm --no-deps app php artisan key:generate
```

Não execute esse procedimento para substituir a
chave de uma instalação existente.

**6. Instalar e compilar o frontend**

Utilize Node.js 24 em um contêiner temporário:

```bash
docker run --rm --user "$(id -u):$(id -g)" \
  -e HOME=/tmp \
  -e npm_config_cache=/tmp/npm-cache \
  -v "$PWD:/app" -w /app \
  node:24-bookworm-slim \
  sh -c 'npm ci --ignore-scripts --no-audit --no-fund && npm run build'
```

O comando utiliza o arquivo `package-lock.json` e
gera os arquivos compilados em `public/build`.

**7. Preparar as permissões do Laravel**

Em uma instalação nova, prepare as pastas de escrita
e proteja o arquivo `.env`:

```bash
docker compose run --rm --no-deps app sh -c '
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
chgrp www-data .env
chmod 640 .env
'
```

Essas permissões permitem que o PHP-FPM escreva
arquivos temporários e leia a configuração da aplicação.

**8. Iniciar os serviços (instalação nova)**

Execute `docker compose up -d db redis app`.

**9. Criar as tabelas (somente instalação nova)**

Execute `docker compose exec -T app php artisan migrate --force`.

**10. Iniciar Nginx e worker**

Execute `docker compose up -d nginx worker`.
Confira os serviços com `docker compose ps`.

**11. Verificar a aplicação**

Acesse `http://localhost:8080` na máquina que executa o Docker.

Para acesso remoto, utilize um túnel SSH.


### Ambiente existente: iniciar os contêineres

Em um ambiente já configurado:

```bash
docker compose up -d
```

### Verificar os serviços

```bash
docker compose ps
```

### Verificar as migrações existentes

```bash
docker compose exec -T app php artisan migrate:status
```

### Acessar a aplicação

No ambiente local configurado:

http://localhost:8080

A porta de acesso pode variar conforme a configuração
do Docker e do servidor.

---

## Segurança

O PingMap incorpora mecanismos de proteção, incluindo:

- Autenticação de usuários;
- Validação de entradas;
- Bloqueio de destinos privados ou reservados em consultas aplicáveis;
- Limitação de requisições;
- Proteção CSRF;
- Limites de tempo para operações externas;
- Tratamento controlado de erros;
- Testes automatizados de segurança.

Na ferramenta BGP, consultas por domínio utilizam
DNS-over-HTTPS com limites de tempo, quantidade de
resultados e recuperação controlada de falhas temporárias.

**Observação:** o projeto está em desenvolvimento.
Antes de uma implantação pública, são necessárias
revisões adicionais de segurança, configuração e operação.

---

## Testes automatizados

O desenvolvimento utiliza práticas de TDD:

**RED → GREEN → REFACTOR**

### Executar todos os testes

```bash
docker compose exec -T app php artisan test
```

### Executar os testes BGP

```bash
docker compose exec -T app php artisan test --filter=Bgp
```

### Última validação completa

| Indicador | Resultado |
|---|---|
| Testes Laravel | 791 aprovados |
| Asserções Laravel | 1.838 |
| Exemplos JSON da API | 58 válidos |

Os resultados correspondem à execução local dos testes.
A execução no GitHub Actions será confirmada após
a publicação dos commits.

### Integração contínua (CI)

O projeto possui um workflow em
`.github/workflows/ci.yml`, configurado para executar
automaticamente em pushes e pull requests para `main`.

O pipeline possui dois jobs:

- **PHP:** configura PHP 8.4, instala dependências,
  compila o frontend e executa os testes Laravel.
- **JavaScript:** configura Node.js 24 e executa
  os testes automatizados de JavaScript.

No ambiente de testes Laravel, o banco utiliza
SQLite em memória, com fila `sync`, cache `array`
e sessões `array`.

---

## Documentação técnica

A documentação detalhada está disponível na pasta `docs/`.

### Sprint 43 — Consultas BGP por rede

[Consultar documentação técnica do Sprint 43](docs/sprint-43-bgp-rede.md)

O documento apresenta:

- Objetivos e arquitetura;
- Componentes e serviços;
- Endpoint da API;
- Exemplos de requisição e resposta;
- Medidas de segurança;
- Estratégia de testes;
- Tratamento de falhas DNS;
- Instruções de operação.

---

## Desenvolvimento e evolução

O PingMap é um projeto em evolução contínua, desenvolvido
para aplicar conhecimentos de:

- Engenharia de software;
- Desenvolvimento PHP e Laravel;
- Redes de computadores;
- Segurança de aplicações;
- Docker e DevOps;
- Testes automatizados;
- Integração com serviços externos.

**Entrega do Sprint 43:** `8927ea4`

---

## Repositório

https://github.com/devhadis/PingMap

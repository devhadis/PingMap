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

---

## Executando o projeto

### Pré-requisitos

- Docker;
- Docker Compose;
- Arquivo `.env` configurado;
- Acesso às dependências externas necessárias.

### Iniciar os contêineres

Em um ambiente já configurado:

```bash
docker compose up -d --build
```

### Verificar os serviços

```bash
docker compose ps
```

### Executar as migrações

```bash
docker compose exec -T app php artisan migrate
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

### Resultado validado no Sprint 43

| Indicador | Resultado |
|---|---|
| Testes da aplicação | 728 aprovados |
| Asserções | 1.598 |
| Testes BGP | 109 aprovados |
| Asserções BGP | 237 |

Esses números representam a execução validada em
24/10/2026 e podem mudar com a evolução do projeto.

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

# PingMap — Referência das APIs

## 1. Introdução

O PingMap disponibiliza endpoints HTTP para executar
consultas e diagnósticos de rede.

As rotas estão organizadas em arquivos específicos
no diretório `routes/`.

Esta documentação descreve os contratos confirmados
nos controllers e nas rotas Laravel.

## 2. Autenticação e segurança

As APIs descritas neste documento utilizam:

- Autenticação por sessão Laravel;
- Middleware `web`;
- Proteção CSRF nas requisições POST;
- Validação de parâmetros;
- Limitação de requisições;
- Tratamento controlado de falhas externas.

As três APIs RDAP e BGP possuem o middleware:

`throttle:10,1`

Para requisições JSON, utilize o cabeçalho:

`Accept: application/json`

As requisições feitas pelo navegador autenticado
devem utilizar a sessão e o token CSRF válidos.

## 3. API RDAP

### Endpoint

`POST /api/v1/rdap/consultas`

### Finalidade

Consultar informações de registro de um endereço
IPv4 ou IPv6 público.

### Parâmetros

| Campo | Tipo | Obrigatório | Validação |
|---|---|---|---|
| ip | string | Sim | Máximo de 45 caracteres |

O endereço também passa por validação de IP público.

### Exemplo de requisição

~~~json
{
  "ip": "8.8.8.8"
}
~~~

### Resposta de sucesso

HTTP 200.

A resposta contém os campos normalizados pelo
`RdapRespostaIpParser`.

Exemplo ilustrativo de estrutura:

~~~json
{
  "resultado": {
    "handle": null,
    "nome": null,
    "inicio": "8.8.8.0",
    "fim": "8.8.8.255",
    "versao": "IPv4",
    "pais": null
  }
}
~~~

Os campos `handle`, `nome` e `pais` podem ser nulos.
Os valores concretos dependem do servidor RDAP.

O parser também confirma que o IP consultado pertence
ao intervalo informado na resposta.

### Erro de endereço inválido

HTTP 422.

~~~json
{
  "message": "Endereço IP inválido ou não público.",
  "errors": {
    "ip": ["Descrição do erro de validação"]
  }
}
~~~

### Falha na consulta

HTTP 503.

~~~json
{
  "message": "Não foi possível concluir a consulta RDAP."
}
~~~

## 4. API BGP por rede

### Endpoint

`POST /api/v1/bgp/rede/consultas`

### Finalidade

Consultar informações de roteamento BGP.

### Parâmetros

| Campo | Tipo | Obrigatório | Validação |
|---|---|---|---|
| alvo | string | Sim | Máximo de 253 caracteres |

O valor passa pelo serviço `BgpAlvoValidador`.

### Exemplo de requisição

~~~json
{
  "alvo": "8.8.8.8"
}
~~~

### Resposta de sucesso

HTTP 200.

A resposta depende do tipo de alvo consultado.

#### Exemplo de resposta por IP

~~~json
{
  "resultado": {
    "tipo": "ip",
    "consulta": "8.8.8.8",
    "prefixo": "8.8.8.0/24",
    "anunciado": true,
    "asns": [15169],
    "coberto_por_prefixo_maior": false
  }
}
~~~

O campo `prefixo` pode ser nulo quando não há
prefixo anunciado para o IP.

#### Resposta por CIDR

O resultado utiliza os mesmos seis campos do exemplo
anterior, com `tipo` igual a `cidr`.

O campo `coberto_por_prefixo_maior` informa se a rede
consultada está coberta por um prefixo menos específico.

#### Resposta por domínio

O resultado contém:

- `tipo`: valor `dominio`;
- `consulta`: domínio normalizado;
- `resultados`: lista de resultados BGP por IP.

Cada elemento de `resultados` utiliza a estrutura
de resposta por endereço IP.

Os valores de prefixo e ASN apresentados acima são
ilustrativos e podem variar conforme a RIPEstat.

### Erro de alvo inválido

HTTP 422.

~~~json
{
  "message": "Alvo BGP inválido.",
  "errors": {
    "alvo": ["Descrição do erro de validação"]
  }
}
~~~

### Falha na consulta

HTTP 503.

~~~json
{
  "message": "Não foi possível concluir a consulta BGP por rede."
}
~~~

## 5. API BGP por ASN

### Endpoint

`POST /api/v1/bgp/consultas`

### Finalidade

Consultar informações de um sistema autônomo (ASN)
e seus prefixos associados.

### Parâmetros

| Campo | Tipo | Obrigatório | Validação |
|---|---|---|---|
| asn | string | Sim | Máximo de 32 caracteres |

O valor passa pelo serviço `BgpAsnValidador`.

### Exemplo de requisição

~~~json
{
  "asn": "AS15169"
}
~~~

### Resposta de sucesso

HTTP 200.

A resposta combina informações do ASN e seus
prefixos IPv4 e IPv6.

Exemplo ilustrativo:

~~~json
{
  "resultado": {
    "asn": 15169,
    "nome": null,
    "anunciado": true,
    "prefixos": {
      "ipv4": ["8.8.8.0/24"],
      "ipv6": []
    }
  }
}
~~~

O campo `nome` pode ser nulo.

O campo `prefixos` contém duas listas, `ipv4` e
`ipv6`, que podem estar vazias.

Os prefixos apresentados são exemplos de formato,
não uma listagem atualizada de anúncios BGP.

### Erro de ASN inválido

HTTP 422.

~~~json
{
  "message": "ASN inválido.",
  "errors": {
    "asn": ["Descrição do erro de validação"]
  }
}
~~~

### Falha na consulta

HTTP 503.

~~~json
{
  "message": "Não foi possível concluir a consulta BGP."
}
~~~

## 6. Outros códigos HTTP

Além dos códigos retornados diretamente pelos
controllers, os middlewares podem produzir:

- 419: falha de validação CSRF;
- 429: limite de requisições excedido;
- Redirecionamento ou erro de autenticação quando
  a sessão não estiver autenticada.

A representação do erro pode variar conforme os
cabeçalhos da requisição.

## 7. Arquivos de referência

Rotas:

- `routes/rdap.php`
- `routes/bgp.php`

Controllers:

- `app/Http/Controllers/Api/RdapConsultaController.php`
- `app/Http/Controllers/Api/BgpConsultaRedeController.php`
- `app/Http/Controllers/Api/BgpConsultaController.php`

## 8. Próximas ampliações

- Detalhar os campos das respostas de sucesso;
- Documentar as demais APIs do PingMap;
- Incluir exemplos completos de integração;
- Documentar contratos de respostas assíncronas;
- Consolidar exemplos de erros de validação.

## 9. Consultas DNS e DNSSEC

O PingMap disponibiliza quatro endpoints para consultas DNS
e análises DNSSEC.

Todas as rotas exigem autenticação Laravel e utilizam
proteção CSRF nas requisições POST.

### 9.1. Criar consulta DNS

**Endpoint:** `POST /api/v1/dns/consultas`

**Limite:** 10 requisições por minuto.

A API cria uma consulta DNS no banco de dados e envia
um Job para processamento assíncrono.

**Parâmetros obrigatórios:**

| Campo | Tipo | Descrição |
|---|---|---|
| `dominio` | string | Domínio público, máximo de 253 caracteres |
| `tipo` | string | Tipo DNS, máximo de 8 caracteres |

Tipos permitidos: A, AAAA, MX, TXT, NS, CNAME,
SOA, PTR e CAA.

Consultas PTR exigem um endereço IP público permitido.

**Exemplo de requisição:**

~~~json
{
  "dominio": "cloudflare.com",
  "tipo": "A"
}
~~~

**Resposta HTTP 202:**

~~~json
{
  "id": 123,
  "dominio": "cloudflare.com",
  "tipo": "A",
  "status": "pendente"
}
~~~

O ID é ilustrativo.

A resposta 202 confirma a criação da consulta,
mas não significa que o processamento DNS terminou.

**Erros possíveis:**

- HTTP 422: parâmetros inválidos ou domínio não permitido.
- HTTP 429: limite de requisições excedido.

### 9.2. Listar consultas DNS

**Endpoint:** `GET /api/v1/dns/consultas`

**Limite:** 60 requisições por minuto.

Retorna as últimas 20 consultas DNS do usuário
autenticado, ordenadas pelo ID mais recente.

**Resposta HTTP 200:**

~~~json
[
  {
    "id": 123,
    "dominio": "cloudflare.com",
    "tipo": "A",
    "status": "pendente",
    "quantidade": null,
    "created_at": "2026-10-25T12:00:00.000000Z"
  }
]
~~~

Os valores são ilustrativos.

A resposta é um array JSON. Quando o usuário
não possui consultas, o resultado é `[]`.

**Erros possíveis:**

- HTTP 429: limite de requisições excedido.


### 9.3. Consultar resultado DNS

**Endpoint:** `GET /api/v1/dns/consultas/{id}`

**Limite:** 60 requisições por minuto.

Retorna os detalhes de uma consulta DNS pertencente
ao usuário autenticado.

O parâmetro `id` deve ser numérico.

**Resposta HTTP 200:**

~~~json
{
  "id": 123,
  "dominio": "cloudflare.com",
  "tipo": "A",
  "status": "sem_registros",
  "quantidade": 0,
  "registros": [],
  "erro": null,
  "created_at": "2026-10-25T12:00:00.000000Z",
  "updated_at": "2026-10-25T12:00:01.000000Z"
}
~~~

Os valores apresentados são ilustrativos.

**Estados possíveis:**

| Estado | Significado |
|---|---|
| `pendente` | Aguardando processamento |
| `executando` | Consulta em andamento |
| `sucesso` | Registros DNS encontrados |
| `sem_registros` | Nenhum registro encontrado |
| `erro` | Falha no processamento |

O campo `registros` contém os resultados DNS.
Sua estrutura depende do tipo de registro consultado.

Os campos `quantidade`, `registros` e `erro`
podem ser nulos conforme o estado da consulta.

**Erros possíveis:**

- HTTP 404: consulta inexistente ou de outro usuário.
- HTTP 429: limite de requisições excedido.

### 9.4. Analisar segurança DNSSEC

**Endpoint:** `POST /api/v1/dnssec/analises`

**Limite:** 10 requisições por minuto.

Analisa a validação DNSSEC de um domínio público.

**Parâmetro obrigatório:**

- `dominio`: string, máximo de 253 caracteres.

**Exemplo de requisição:**

~~~json
{
  "dominio": "cloudflare.com"
}
~~~

**Resposta HTTP 200:**

~~~json
{
  "resultado": {
    "dominio": "cloudflare.com",
    "tipo": "A",
    "estado": "seguro"
  }
}
~~~

O estado apresentado é ilustrativo.

**Estados possíveis:**

| Estado | Significado |
|---|---|
| `seguro` | Validação confirmada com registro A válido |
| `inseguro` | Resposta DNS não assinada |
| `indeterminado` | Validação não pôde ser confirmada |

O validador DNSSEC utiliza o utilitário `delv`,
com resolvedor `1.1.1.1` e timeout de 12 segundos.

**Erros possíveis:**

- HTTP 422: domínio inválido ou não permitido.
- HTTP 503: falha ao concluir a análise DNSSEC.
- HTTP 429: limite de requisições excedido.

**Segurança:**

- Autenticação Laravel obrigatória.
- Proteção CSRF para requisições POST.
- Validação do domínio antes da consulta externa.

## 10. Segurança de e-mail: SPF, DKIM e DMARC

O PingMap oferece um endpoint para analisar registros
DNS relacionados à segurança de e-mail.

As análises consultam registros TXT publicados no DNS.
Elas não verificam a autenticação de uma mensagem
de e-mail específica.

### 10.1. Endpoint e parâmetros

**Endpoint:** `POST /api/v1/email/analises`

**Autenticação:** obrigatória.

**Limite:** 10 requisições por minuto.

**Parâmetros:**

| Campo | Obrigatório | Descrição |
|---|---|---|
| `dominio` | Sim | Domínio público, máximo de 253 caracteres |
| `tipo` | Sim | SPF, DKIM ou DMARC |
| `seletor` | Para DKIM | Seletor de até 63 caracteres |

O campo `tipo` deve ser enviado em letras maiúsculas.

O seletor DKIM aceita letras, números e hífens,
respeitando as regras de validação do controller.

**Exemplo de requisição SPF:**

~~~json
{
  "dominio": "example.com",
  "tipo": "SPF"
}
~~~

A análise é executada durante a própria requisição.

### 10.2. Análise SPF

Para executar uma análise SPF, envie `tipo` como `SPF`.

O serviço consulta registros TXT do domínio e
identifica os registros iniciados por `v=spf1`.

**Exemplo de resposta HTTP 200:**

~~~json
{
  "resultado": {
    "dominio": "example.com",
    "tipo": "SPF",
    "status": "presente",
    "quantidade": 1,
    "politica": "fail",
    "registros": [
      "v=spf1 -all"
    ]
  }
}
~~~

O exemplo é ilustrativo e não representa
uma consulta DNS executada.

**Estados possíveis:**

| Estado | Significado |
|---|---|
| `ausente` | Nenhum registro SPF encontrado |
| `presente` | Um registro SPF encontrado |
| `multiplo` | Mais de um registro SPF encontrado |

**Políticas identificadas:**

| Política | Mecanismo SPF |
|---|---|
| `fail` | `-all` |
| `softfail` | `~all` |
| `neutral` | `?all` |
| `pass` | `+all` ou `all` |
| `nao_definida` | Sem mecanismo `all` reconhecido |

O campo `politica` pode ser nulo quando
não existe exatamente um registro SPF.

### 10.3. Análise DKIM

Para executar uma análise DKIM, envie `tipo`
como `DKIM` e informe o campo `seletor`.

O serviço consulta registros TXT no endereço
`seletor._domainkey.dominio`.

**Exemplo de requisição:**

~~~json
{
  "dominio": "example.com",
  "tipo": "DKIM",
  "seletor": "mail"
}
~~~

**Exemplo de resposta HTTP 200:**

~~~json
{
  "resultado": {
    "dominio": "example.com",
    "seletor": "mail",
    "nome": "mail._domainkey.example.com",
    "tipo": "DKIM",
    "status": "presente",
    "quantidade": 1,
    "registros": [
      "v=DKIM1; k=rsa; p=CHAVE_ILUSTRATIVA"
    ]
  }
}
~~~

Os dados são ilustrativos.
A chave apresentada não é uma chave pública real.

**Estados possíveis:**

| Estado | Significado |
|---|---|
| `ausente` | Nenhum registro DKIM encontrado |
| `presente` | Um registro DKIM com campo `p=` preenchido |
| `invalido` | Um registro DKIM sem campo `p=` preenchido |
| `multiplo` | Mais de um registro DKIM encontrado |

**Campos da resposta:**

| Campo | Descrição |
|---|---|
| `dominio` | Domínio analisado |
| `seletor` | Seletor DKIM normalizado |
| `nome` | Nome DNS consultado |
| `tipo` | Identificação DKIM |
| `status` | Estado da análise |
| `quantidade` | Número de registros encontrados |
| `registros` | Registros DKIM identificados |

O estado `presente` não garante que a chave
seja criptograficamente válida.

O serviço verifica a presença do campo `p=`,
mas não valida assinaturas de mensagens.

### 10.4. Análise DMARC

Para executar uma análise DMARC, envie `tipo`
como `DMARC`.

O serviço consulta registros TXT no endereço
`_dmarc.dominio` e identifica registros `v=DMARC1`.

**Exemplo de requisição:**

~~~json
{
  "dominio": "example.com",
  "tipo": "DMARC"
}
~~~

**Exemplo de resposta HTTP 200:**

~~~json
{
  "resultado": {
    "dominio": "example.com",
    "tipo": "DMARC",
    "status": "presente",
    "politica": "reject",
    "quantidade": 1,
    "registros": [
      "v=DMARC1; p=reject"
    ]
  }
}
~~~

Os valores apresentados são ilustrativos.

**Estados possíveis:**

| Estado | Significado |
|---|---|
| `ausente` | Nenhum registro DMARC encontrado |
| `presente` | Um registro com política reconhecida |
| `invalido` | Registro com configuração não aceita |
| `multiplo` | Mais de um registro DMARC encontrado |

**Políticas reconhecidas:**

| Política | Significado |
|---|---|
| `none` | Monitoramento sem solicitar quarentena ou rejeição |
| `quarantine` | Solicita tratamento como mensagem suspeita |
| `reject` | Solicita rejeição da mensagem |

O serviço exige que a política `p` esteja
na segunda posição do registro DMARC.

Também verifica campos duplicados e aceita
somente as políticas `none`, `quarantine` e `reject`.

O campo `politica` pode ser nulo quando
o registro está ausente, inválido ou múltiplo.

A análise verifica a configuração DNS publicada,
mas não autentica mensagens individuais.

### 10.5. Erros e segurança

**Códigos HTTP:**

| Código | Significado |
|---|---|
| `200` | Análise concluída |
| `422` | Parâmetros ou domínio inválidos |
| `429` | Limite de requisições excedido |
| `503` | Falha ao concluir a análise DNS |

**Exemplo de erro HTTP 503:**

~~~json
{
  "message": "Não foi possível concluir a análise DNS."
}
~~~

**Regras de segurança:**

- Autenticação Laravel obrigatória.
- Proteção CSRF para requisições POST.
- Limite de 10 requisições por minuto.
- Validação do domínio antes da consulta DNS.
- Seletor obrigatório nas análises DKIM.
- Resposta genérica em falhas inesperadas.

Requisições sem autenticação podem receber
redirecionamento ou erro HTTP 401, conforme
a negociação de resposta do Laravel.

Falhas na verificação CSRF podem gerar HTTP 419.

## 11. Diagnóstico HTTP e TLS

O PingMap disponibiliza uma API para consultar
informações HTTP e TLS de domínios públicos.

O serviço realiza uma requisição HEAD utilizando
HTTPS e retorna metadados da conexão.

### 11.1. Endpoint e parâmetros

**Endpoint:** `POST /api/v1/http-tls/consultas`

**Autenticação:** obrigatória.

**Limite:** 10 requisições por minuto.

**Parâmetro obrigatório:**

| Campo | Tipo | Descrição |
|---|---|---|
| `dominio` | string | Domínio público, máximo de 253 caracteres |

**Exemplo de requisição:**

~~~json
{
  "dominio": "example.com"
}
~~~

O domínio é validado e normalizado antes da consulta.

A consulta é síncrona e utiliza HTTPS.

### 11.2. Resposta da consulta HTTP/TLS

Quando a consulta é concluída, a API retorna
HTTP 200 com os dados dentro de `resultado`.

**Exemplo ilustrativo de resposta HTTP 200:**

~~~json
{
  "resultado": {
    "dominio": "example.com",
    "ips": [
      "8.8.8.8"
    ],
    "ip_conectado": "8.8.8.8",
    "status_http": 200,
    "tempo_ms": 120.5,
    "cabecalhos": {
      "content-type": "text/html"
    },
    "certificado": {
      "assunto": "CN = example.com",
      "emissor": "CN = AC de teste",
      "dominios": [
        "example.com"
      ],
      "inicio_validade": "2026-09-26T22:49:11+00:00",
      "fim_validade": "2026-12-25T22:56:35+00:00",
      "algoritmo_assinatura": "ecdsa-with-SHA256",
      "certificados_na_cadeia": 2,
      "dias_restantes": 62,
      "expirado": false,
      "alerta_expiracao": false
    }
  }
}
~~~

Os valores apresentados são dados simulados
utilizados nos testes automatizados do PingMap.

**Campos principais:**

| Campo | Descrição |
|---|---|
| `dominio` | Domínio normalizado |
| `ips` | IPs públicos resolvidos |
| `ip_conectado` | IP utilizado na conexão HTTPS |
| `status_http` | Código HTTP retornado pelo destino |
| `tempo_ms` | Tempo total da consulta, em milissegundos |
| `cabecalhos` | Cabeçalhos HTTP selecionados |
| `certificado` | Metadados TLS ou `null` quando indisponíveis |

O código `status_http` pertence ao servidor
consultado e não deve ser confundido com o
código HTTP da resposta da API PingMap.

### 11.3. Certificado TLS e cabeçalhos HTTP

O campo `certificado` apresenta os metadados
obtidos da conexão HTTPS.

Quando os metadados do certificado estão
indisponíveis, o campo recebe `null`.

**Campos do certificado:**

| Campo | Descrição |
|---|---|
| `assunto` | Identificação do titular do certificado |
| `emissor` | Autoridade ou entidade emissora |
| `dominios` | Nomes de domínio informados no certificado |
| `inicio_validade` | Início do período de validade |
| `fim_validade` | Fim do período de validade |
| `algoritmo_assinatura` | Algoritmo de assinatura informado |
| `certificados_na_cadeia` | Quantidade de certificados na cadeia |
| `dias_restantes` | Dias restantes de validade |
| `expirado` | Indica se o certificado está expirado |
| `alerta_expiracao` | Indica alerta de proximidade da expiração |

Os campos de validade utilizam datas e horários
interpretados pelo serviço de certificados TLS.

O cálculo dos dias restantes e do alerta de
expiração é realizado pelo parser do certificado.

**Cabeçalhos HTTP:**

O PingMap retorna somente os cabeçalhos
permitidos pelo serviço:

- `content-type`
- `server`
- `location`
- `strict-transport-security`
- `content-security-policy`
- `x-frame-options`
- `x-content-type-options`
- `referrer-policy`
- `cache-control`

Cabeçalhos ausentes não são incluídos na resposta.

Os valores apresentados têm caracteres de
controle removidos e são limitados a 512 bytes.

A presença de um cabeçalho não garante,
isoladamente, que o servidor esteja seguro.

### 11.4. Segurança e tratamento de erros

A API HTTP/TLS utiliza mecanismos de proteção
para restringir as conexões realizadas pelo servidor.

**Proteções configuradas:**

- Autenticação Laravel obrigatória.
- Limite de 10 requisições por minuto.
- Validação e normalização do domínio informado.
- Validação dos endereços IP resolvidos.
- Conexão restrita ao protocolo HTTPS.
- Verificação do certificado TLS habilitada.
- Redirecionamentos HTTP desabilitados.
- Utilização de proxy desabilitada.
- Uso de cURL para fixar os IPs aprovados.
- Validação do IP efetivamente conectado.
- Timeout de conexão de 3 segundos.
- Timeout total da requisição de 8 segundos.
- Remoção de caracteres de controle dos cabeçalhos.
- Limite de 512 bytes por valor de cabeçalho.

Essas medidas reduzem os riscos associados
a consultas de destinos não autorizados.

**Validação do certificado TLS:**

O parser verifica os campos e as datas do
certificado e aceita cadeias de até 16 certificados.

O resultado contém somente os metadados
necessários, sem expor o certificado PEM completo.

O campo `expirado` indica se o certificado
atingiu ou ultrapassou a data de expiração.

O campo `alerta_expiracao` é verdadeiro quando
restam 30 dias ou menos de validade, incluindo
certificados já expirados.

**Códigos HTTP da API:**

| Código | Significado |
|---|---|
| `200` | Consulta concluída |
| `401` | Requisição JSON sem autenticação |
| `422` | Domínio inválido ou destino não permitido |
| `429` | Limite de requisições excedido |
| `503` | Falha ao concluir a consulta HTTP/TLS |

Falhas de verificação CSRF também podem
produzir HTTP 419 quando essa proteção é aplicada.

**Exemplo de erro HTTP 422:**

~~~json
{
  "message": "Domínio inválido.",
  "errors": {
    "dominio": [
      "Informe um domínio público válido."
    ]
  }
}
~~~

**Exemplo de erro HTTP 503:**

~~~json
{
  "message": "Não foi possível concluir a consulta HTTP/TLS."
}
~~~

Erros inesperados são registrados internamente,
mas seus detalhes não são expostos na resposta.

O código HTTP da API deve ser diferenciado
do campo `status_http`, que representa a
resposta recebida do servidor consultado.

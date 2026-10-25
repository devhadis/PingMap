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

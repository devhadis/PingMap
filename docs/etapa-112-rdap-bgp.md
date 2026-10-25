# PingMap — Etapa 112: Integração RDAP + BGP

**Projeto:** PingMap  
**Tecnologias:** PHP 8.4, Laravel 13, JavaScript e Docker  
**Commit:** `24bece1`  
**Status:** Implementado e testado

## 1. Objetivo

Integrar as ferramentas RDAP e BGP Lookup para permitir
que o usuário consulte informações de roteamento de um
endereço IP diretamente após uma consulta RDAP.

A integração reutiliza as APIs existentes e não exige
uma nova consulta manual na página BGP.

## 2. Funcionamento

O fluxo da aplicação é:

1. O usuário informa um endereço IP público.
2. A interface envia a consulta à API RDAP.
3. O resultado RDAP é apresentado.
4. O botão "Consultar BGP deste IP" fica disponível.
5. Ao clicar, a interface consulta a API BGP.
6. As informações de roteamento são apresentadas
   sem remover os dados RDAP.

## 3. Endpoints utilizados

### RDAP

**Método:** POST  
**Rota:** `/api/v1/rdap/consultas`

Exemplo de requisição:

~~~json
{
  "ip": "8.8.8.8"
}
~~~

### BGP

**Método:** POST  
**Rota:** `/api/v1/bgp/rede/consultas`

Exemplo de requisição:

~~~json
{
  "alvo": "8.8.8.8"
}
~~~

Exemplo de resposta:

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

## 4. Arquivos envolvidos

Interface:

`resources/views/ferramentas/rdap.blade.php`

Teste PHP:

`tests/Feature/RdapBgpIntegracaoInterfaceTest.php`

Testes JavaScript:

`tests/js/rdap-bgp-integracao.test.mjs`

A implementação reutiliza os controllers e serviços
RDAP e BGP já existentes.

## 5. Segurança e confiabilidade

A integração utiliza:

- Sessão autenticada do Laravel;
- Token CSRF nas requisições;
- Validação de endereços IP públicos;
- Limitação de requisições na API BGP;
- Tratamento de falhas HTTP;
- Renderização de dados externos como texto;
- Controle de respostas assíncronas antigas.

Se a consulta BGP falhar, o resultado RDAP permanece
disponível.

Ao iniciar outra consulta RDAP, os dados BGP anteriores
são limpos e respostas atrasadas são ignoradas.

## 6. Testes automatizados

A integração possui testes para:

1. Consulta BGP após RDAP com apresentação de prefixo e ASN;
2. Preservação dos dados RDAP quando a API BGP falha;
3. Descarte de respostas BGP antigas após nova consulta RDAP.

### Executar o teste PHP

~~~bash
docker compose exec -T app php artisan test tests/Feature/RdapBgpIntegracaoInterfaceTest.php
~~~

### Executar os testes JavaScript

~~~bash
docker run --rm \
  -v "$PWD:/projeto:ro" \
  -w /projeto \
  node:22-alpine \
  node --test tests/js/rdap-bgp-integracao.test.mjs
~~~

## 7. Resultado da validação

Na conclusão da Etapa 112:

- 791 testes PHP aprovados;
- 68 testes JavaScript aprovados;
- 859 testes aprovados no total;
- Integração validada no navegador;
- Consultas RDAP e BGP retornando HTTP 200;
- Repositório Git sem alterações pendentes após o commit.

## 8. Referências internas

Documentação da ferramenta BGP:

`docs/sprint-43-bgp-rede.md`

Commit da integração:

`24bece1`

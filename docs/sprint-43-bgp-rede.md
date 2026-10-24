# PingMap — Sprint 43: Consultas BGP por IP, CIDR e domínio

**Projeto:** PingMap
**Stack:** PHP 8.4, Laravel 13, Docker, Nginx, PostgreSQL e Redis
**Data de conclusão:** 24/10/2026
**Commit:** `8927ea4`
**Status:** Implementado, testado e publicado no GitHub

---

## 1. Objetivo

Expandir a ferramenta BGP Lookup do PingMap para permitir
consultas por:

- Endereço IPv4 público;
- Endereço IPv6 público;
- Prefixo CIDR IPv4;
- Prefixo CIDR IPv6;
- Domínio público.

A implementação preserva a consulta BGP por ASN existente
e adiciona um novo fluxo de consulta por rede.

## 2. Arquitetura

A aplicação utiliza uma arquitetura baseada em controller,
serviços especializados e integração com APIs externas.

### Fluxo principal

```text
Usuário
  |
  v
Página BGP Lookup
  |
  v
POST /api/v1/bgp/rede/consultas
  |
  v
BgpConsultaRedeController
  |
  v
BgpAlvoValidador
  |
  v
BgpConsultaRedeService
  |
  +-- IPv4 / IPv6 / CIDR
  |       |
  |       v
  |   RIPEstat network-info
  |
  +-- Domínio
          |
          v
      BgpResolucaoDominioService
          |
          v
      BgpDnsConsultaProtegida
          |
          v
      DNS-over-HTTPS
          |
          v
      Endereços IP públicos
          |
          v
      RIPEstat network-info
          |
          v
      Resultado BGP
```

## 3. Componentes

### BgpConsultaRedeController

Arquivo:

`app/Http/Controllers/Api/BgpConsultaRedeController.php`

Responsável por receber as requisições, validar a entrada,
executar o serviço e retornar a resposta JSON.

### BgpAlvoValidador

Arquivo:

`app/Services/BgpAlvoValidador.php`

Identifica e normaliza o tipo de entrada:

- `ip`
- `cidr`
- `dominio`

Rejeita entradas inválidas, destinos não públicos e
formatos potencialmente perigosos.

### BgpConsultaRedeService

Arquivo:

`app/Services/BgpConsultaRedeService.php`

Consulta informações de roteamento BGP utilizando a API
RIPEstat e organiza os resultados para a interface.

### BgpResolucaoDominioService

Arquivo:

`app/Services/BgpResolucaoDominioService.php`

Resolve domínios para endereços IPv4 e IPv6 públicos,
remove duplicatas e limita a quantidade de endereços
consultados.

### BgpDnsConsultaProtegida

Arquivo:

`app/Services/BgpDnsConsultaProtegida.php`

Realiza consultas DNS-over-HTTPS para registros A e AAAA.

Inclui limites de conexão, transferência e recuperação
controlada de falhas temporárias.

### Interface web

Arquivo:

`resources/views/ferramentas/partials/bgp_rede.blade.php`

Apresenta o formulário e os resultados BGP em cartões,
integrados à página BGP Lookup.

## 4. Endpoint da API

### Consultar BGP por rede

**Método:** `POST`

**Rota:**

`/api/v1/bgp/rede/consultas`

**Autenticação:** sessão autenticada do Laravel.

**Proteção:** CSRF e limite de 10 requisições por minuto.

### Corpo da requisição

```json
{
  "alvo": "8.8.8.8"
}
```

Também são aceitos exemplos como:

```text
8.8.8.0/24
2001:4860:4860::8888
2001:4860::/32
dns.google
```

### Exemplo de resposta IPv4

```json
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
```

### Exemplo de resposta por domínio

A resposta possui o tipo `dominio`, o domínio consultado
e uma lista de resultados individuais para os IPs
encontrados.

Estrutura simplificada:

```json
{
  "resultado": {
    "tipo": "dominio",
    "consulta": "dns.google",
    "resultados": [
      {
        "tipo": "ip",
        "consulta": "8.8.8.8",
        "prefixo": "8.8.8.0/24",
        "anunciado": true,
        "asns": [15169],
        "coberto_por_prefixo_maior": false
      }
    ]
  }
}
```

Os dados BGP e DNS são dinâmicos. Os valores apresentados
são exemplos observados durante os testes do Sprint 43.

## 5. Segurança

A implementação adota as seguintes medidas:

| Proteção | Implementação |
|---|---|
| Autenticação | Middleware `auth` |
| Limitação de requisições | `throttle:10,1` |
| Proteção CSRF | Integração com sessão Laravel |
| IPs privados | Rejeitados pela validação |
| Endereços reservados | Rejeitados pela validação |
| Entradas maliciosas | Validação e normalização |
| DNS externo | HTTPS |
| Redirecionamentos DNS HTTP | Desabilitados |
| Respostas DNS grandes | Limite de 65.536 bytes |
| Registros DNS excessivos | Limite de 128 registros |
| IPs consultados por domínio | Máximo de 8 |
| Prazo por domínio | 12 segundos compartilhados |
| Falha temporária de conexão DNS | No máximo 2 tentativas por tipo |
| Falhas externas | Resposta HTTP 503 controlada |

O serviço não deve realizar consultas BGP externas para
endereços privados ou reservados.

A interface utiliza `textContent` para inserir dados
dinâmicos, reduzindo riscos de interpretação de conteúdo
externo como HTML.

## 6. Tratamento de erros

A API utiliza respostas HTTP apropriadas:

- `422`: entrada inválida ou não permitida;
- `429`: limite de requisições excedido;
- `503`: falha de serviço externo ou impossibilidade
  de concluir a consulta.

A autenticação e a proteção CSRF são gerenciadas pelo
Laravel.

Detalhes internos das exceções não devem ser enviados
ao usuário final.

## 7. Recuperação de falhas DNS

Durante a validação no navegador, uma consulta ao domínio
`dns.google` apresentou erro HTTP 503.

A análise do log identificou:

```text
cURL error 28:
Operation timed out after 4002 milliseconds
with 0 bytes received
```

Foi implementada uma segunda tentativa controlada para
falhas de conexão DNS.

O comportamento é:

1. Realizar a consulta DNS.
2. Se houver falha de conexão, verificar o prazo restante.
3. Se ainda houver tempo, tentar novamente uma única vez.
4. Encerrar após a segunda falha.
5. Não iniciar novas consultas após o prazo compartilhado.

A recuperação não é aplicada automaticamente a respostas
DNS inválidas ou a erros HTTP.

## 8. Testes automatizados

A implementação foi desenvolvida utilizando TDD:

**RED → GREEN → REFACTOR**

Os testes abrangem:

- Validação de IPv4, IPv6, CIDR e domínio;
- Rejeição de endereços privados e reservados;
- Segurança de entradas;
- Resolução DNS por HTTPS;
- Consultas RIPEstat;
- Respostas malformadas;
- Limites de transferência;
- Autenticação e rate limiting;
- Prazo compartilhado;
- Recuperação de falhas DNS;
- Integração da API com a interface.

### Executar testes BGP

```bash
docker compose exec -T app php artisan test --filter=Bgp
```

Resultado validado:

```text
109 passed (237 assertions)
```

### Executar todos os testes do PingMap

```bash
docker compose exec -T app php artisan test
```

Resultado validado em 24/10/2026:

```text
728 passed (1598 assertions)
Duration: 32.25s
```

## 9. Validação manual

Foram verificadas na interface:

| Entrada | Resultado |
|---|---|
| `8.8.8.8` | Consulta IPv4 concluída |
| `dns.google` | Consulta por domínio concluída |
| `8.8.8.0/24` | Consulta CIDR IPv4 concluída |
| `2001:4860::/32` | Consulta CIDR IPv6 concluída |
| `192.168.1.1` | Endereço privado rejeitado |

## 10. Operação e diagnóstico

### Consultar logs

```bash
docker compose exec -T app \
  tail -n 80 storage/logs/laravel.log
```

### Verificar rotas BGP

```bash
docker compose exec -T app \
  php artisan route:list --path=bgp
```

### Verificar integridade das alterações

```bash
git diff --check
```

### Limitações conhecidas

As consultas dependem da disponibilidade dos serviços
externos de DNS-over-HTTPS e RIPEstat.

Falhas de rede, limites de tempo e indisponibilidade
dos provedores podem impedir a conclusão de uma consulta.

A recuperação de conexão DNS aumenta a tolerância a
falhas transitórias, mas não garante sucesso.

## 11. Resultado do Sprint 43

O Sprint 43 foi concluído com:

- Nova API de consulta BGP por rede;
- Suporte a IPv4, IPv6, CIDR e domínio;
- DNS-over-HTTPS protegido;
- Tratamento de falhas temporárias;
- Interface web integrada;
- Testes automatizados aprovados;
- Publicação no GitHub.

**Commit de entrega:** `8927ea4`

**Repositório:**

https://github.com/devhadis/PingMap

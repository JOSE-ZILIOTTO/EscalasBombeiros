# Escala de Plantões — Bombeiros

Sistema web para montar e acompanhar a escala mensal de plantões de uma equipe de
bombeiros: bombeiros civis (BC), que escolhem plantões por ordem de rodízio, e Fixos,
que trabalham num ciclo 24x72. O administrador monta a escala num calendário, imprime
ou exporta a planilha do mês, e cada bombeiro consulta os próprios plantões no celular.

Escrito em PHP 8.2+ sem framework, com MySQL/MariaDB, e pensado para rodar em
hospedagem compartilhada simples (inclusive gratuita), sem extensões além de PDO.

## Funcionalidades

**Para o administrador**
- Calendário do mês com quem está escalado em cada dia e as vagas livres.
- Registro e remoção de plantões: Diurno (12h), Noturno (12h), Integral (24h) e
  Integral substituto, que cobre a ausência do Fixo.
- Ciclo 24x72 dos Fixos calculado automaticamente, com marcação de ausência por dia.
- Ordem de escolha em rodízio: indica qual BC escolhe o próximo plantão.
- Cadastro de bombeiros (dados pessoais, endereço, contato de emergência, dados
  bancários, tamanhos de uniforme) e de usuários de acesso.
- Planilha mensal para impressão/PDF em A4 paisagem, com assinatura editável.
- Exportação da planilha para Excel (.xlsx).
- Tutorial guiado no primeiro acesso.
- Apagar os dados pessoais de quem saiu, um a um ou todos com prazo de retenção vencido.

**Para o bombeiro** (página "Minha escala")
- Seus plantões do mês, com situação (realizado, hoje, agendado).
- Resumo do mês: total, já realizado e a realizar. Para o Fixo, mostra só a escala,
  sem valores.
- "Meus dados": tudo o que o sistema guarda sobre ele, com download de uma cópia em JSON.

Todas as telas funcionam no celular.

## Regras de negócio

| Regra | Como funciona |
|---|---|
| Vagas de BC | Por dia há N vagas no Diurno e N no Noturno (`VAGAS_BC_POR_TURNO`, padrão 1). O Integral de um BC ocupa as duas. |
| Fixo 24x72 | 1 dia de serviço e 3 de folga, a partir de uma data de referência por Fixo. |
| Substituto | Se o Fixo do dia tem ausência marcada, abre 1 vaga de Integral substituto. |
| Plantões do Fixo | Em dia de folga, um Fixo pode cobrir turnos (trocas) sem ocupar vaga de BC. No próprio dia de ciclo ele não recebe plantão. |
| Sobreposição | A mesma pessoa não pode ter dois turnos com horário em comum no mesmo dia. |
| Remuneração | Tabela por plantão (12h e 24h) configurável no `.env`; usada no resumo dos BCs. |
| Administradores | O sistema impede desativar ou rebaixar o último administrador ativo. |

As regras ficam no domínio (`src/Domain`) e são as mesmas para todas as telas.

## Tecnologias

- PHP 8.2+ (desenvolvido em 8.2; em produção roda em 8.4), `declare(strict_types=1)`, PSR-4 e PSR-12
- MySQL 8 / MariaDB 10.4+ via PDO
- Composer (autoload e `psr/log`); PHPUnit 11 nos testes
- HTML, CSS e JavaScript puros: tema claro/escuro, sem build de front-end

## Arquitetura

```
src/
├── Domain/          regras e modelos: Turno, CicloFixo, RegraDeVagas, Bombeiro, Usuario…
├── Application/     casos de uso: RegistrarPlantao, GerarPlanilhaMensal, ConsultarMeuPortal…
├── Infrastructure/  PDO, sessão, configuração (.env), container de dependências, .xlsx
└── Http/            rotas, controllers, middlewares (erros, método, autenticação, CSRF)
templates/           telas em PHP puro
migrations/          scripts SQL
tests/               PHPUnit
```

- **Entrada:** cada arquivo da raiz (`escala.php`, `api/*.php`…) só chama
  `bootstrap.php` com o nome da rota. Assim, as URLs continuam as mesmas da versão antiga.
- **Cadeia de cada requisição:** HTTPS (opcional) → tratamento de erros → método HTTP →
  autenticação/papel → CSRF → controller.
- **Erros de domínio:** cada exceção vira uma resposta HTTP — dado inválido 400,
  não encontrado 404, regra violada 409.

## Segurança

- Senhas com `password_hash`; sessão regenerada no login.
- Usuário revalidado no banco a cada requisição, para que desativar alguém tenha efeito imediato.
- Limite de tentativas de login por IP.
- Token CSRF em todos os formulários e chamadas que alteram dados.
- Consultas preparadas e saída escapada nas telas.
- `.htaccess` bloqueia `src/`, `templates/`, `vendor/`, `tests/`, `migrations/`, `bin/`, `.env` e
  arquivos de configuração, e envia cabeçalhos de segurança.
- Credenciais só no `.env`, que fica fora do Git.
- Com `FORCAR_HTTPS=1`, todo acesso por `http://` é redirecionado para `https://`, com HSTS.

## Proteção de dados pessoais (LGPD)

| Medida | Como funciona |
|---|---|
| Criptografia em repouso | CPF e dados bancários são gravados com AES-256-GCM (`CHAVE_DADOS`). Um dump ou backup vazado não expõe esses campos. |
| Máscara nas telas | Na edição, CPF e banco aparecem mascarados (`***.456.789-**`). O valor real só vem do servidor quando o administrador clica em "Mostrar" ou "Alterar". |
| Acesso e portabilidade | Em "Meus dados", o bombeiro vê o próprio cadastro e baixa uma cópia em JSON (LGPD, art. 18, II e V). |
| Eliminação | O administrador apaga os dados pessoais de um bombeiro inativo. Nome e plantões ficam no histórico. |
| Retenção | Ao inativar, o sistema registra a data. Passados `RETENCAO_DADOS_INATIVOS_ANOS`, a tela Bombeiros avisa e oferece apagar os vencidos. |
| Transparência | `privacidade.php`: aviso de privacidade público, com link no login e no portal. |

**A `CHAVE_DADOS` é obrigatória e não pode ser perdida.** Sem ela o sistema não abre, e
os CPFs e dados bancários já gravados não podem mais ser lidos. Use uma chave diferente
em cada instalação e guarde uma cópia fora do servidor.

## Instalação

Requisitos: PHP 8.2+ com `pdo_mysql` e `mbstring`, MySQL/MariaDB, Apache com
`AllowOverride All` (ou regras equivalentes às do `.htaccess`) e Composer.

```bash
git clone https://github.com/JOSE-ZILIOTTO/EscalasBombeiros.git
cd EscalasBombeiros
composer install
cp .env.example .env        # preencha DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"   # cole em CHAVE_DADOS no .env
```

**Banco de dados**
1. Instalação nova: rode `migrations/000_esquema_inicial.sql` e depois `migrations/002_lgpd.sql`.
   Banco que já vem da versão antiga: depois de um backup, rode
   `migrations/001_vagas_nome_guerra_limpeza.sql` e `migrations/002_lgpd.sql`. Em seguida,
   rode `php bin/proteger_dados_antigos.php` para criptografar os CPFs e dados bancários que
   já estavam gravados. Pode rodar de novo sem problema.
2. Opcional: nomes de guerra e assinatura da planilha, a partir de
   `migrations/nomes_de_guerra.exemplo.sql`. Copie para `*.local.sql`, que o Git ignora.
3. Crie o primeiro administrador:

```bash
php -r "echo password_hash('troque-esta-senha', PASSWORD_DEFAULT), PHP_EOL;"
```

```sql
INSERT INTO usuarios (nome, usuario, senha_hash, tipo, ativo)
VALUES ('Administrador', 'admin', '<hash gerado acima>', 'admin', 1);
```

Depois, acesse `index.php`. Os demais usuários são criados pela tela **Usuários**.

### Configuração (`.env`)

| Variável | Padrão | Uso |
|---|---|---|
| `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME` | — | conexão com o banco |
| `APP_TIMEZONE` | `America/Sao_Paulo` | define "hoje" e o mês atual |
| `VAGAS_BC_POR_TURNO` | `1` | vagas de BC por turno por dia |
| `REMUNERACAO_TURNO_12H` / `_24H` | `125` / `250` | valores por plantão, em reais |
| `LOGIN_MAX_TENTATIVAS` / `LOGIN_JANELA_SEGUNDOS` | `5` / `900` | limite de tentativas de login |
| `CHAVE_DADOS` | — (obrigatória) | chave da criptografia de CPF e dados bancários |
| `RETENCAO_DADOS_INATIVOS_ANOS` | `5` | anos que os dados de um inativo ficam guardados |
| `FORCAR_HTTPS` | `0` | `1` em produção, com o certificado SSL já funcionando |
| `PRIVACIDADE_CONTROLADOR` / `PRIVACIDADE_CONTATO` | texto genérico / — | quem trata os dados e onde fazer pedidos, no aviso de privacidade |

## Testes

```bash
composer test
```

São 143 testes de domínio, casos de uso, middlewares e infraestrutura, incluindo a
geração do .xlsx e a criptografia dos dados pessoais. Os testes não precisam de banco de dados.

## Publicação em hospedagem compartilhada

```bash
composer build-producao     # vendor/ sem dependências de desenvolvimento
```

Envie por FTP os arquivos do projeto, incluindo `vendor/` e sem `tests/`, e crie o
`.env` direto no servidor. Mudanças de CSS/JS chegam aos navegadores sem limpar o
cache, porque os endereços desses arquivos incluem a data de alteração.

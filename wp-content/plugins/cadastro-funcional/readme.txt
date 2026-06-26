=== IgesDF User Data ===
Contributors: Marcos Cordeiro
Tags: users, profile, custom fields, user meta, admin, funcional
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para adicionar campos de dados funcionais (Matrícula, Unidade, Setor) aos perfis de usuário no WordPress.

== Description ==

O plugin **Cadastro Funcional de Usuários** expande o perfil padrão do WordPress, permitindo que administradores associem informações funcionais a cada usuário. É ideal para intranets, sistemas de RH ou qualquer ambiente onde seja necessário gerenciar equipes com base em sua estrutura organizacional.

Recursos principais:
*   **Campos Personalizados:** Adiciona os campos "Matrícula", "Unidade de Atendimento" e "Setor" ao perfil de cada usuário.
*   **Gerenciamento Centralizado:** Cria tipos de post personalizados (`Unidades` e `Setores`) para que você possa gerenciar as opções disponíveis de forma centralizada e consistente.
*   **Integração com Admin:** Os novos campos são exibidos na tela de edição de perfil e os dados preenchidos aparecem em novas colunas na lista de usuários, facilitando a visualização rápida.
*   **Fácil de Usar:** A interface é totalmente integrada ao painel do WordPress, tornando a utilização intuitiva para os administradores.

== Installation ==

1.  Faça o upload da pasta `cadastro-funcional` para o diretório `/wp-content/plugins/`.
2.  Ative o plugin através do menu 'Plugins' no painel administrativo do WordPress.
3.  Após a ativação, novos menus aparecerão dentro de **Usuários**.

== How to Use ==

1.  **Cadastre as Unidades:** Vá para **Usuários > Unidades** e adicione as unidades de atendimento da sua organização.
2.  **Cadastre os Setores:** Acesse **Usuários > Setores** para adicionar os diferentes setores ou departamentos.
3.  **Associe os Dados ao Usuário:** Edite o perfil de um usuário em **Usuários > Todos os usuários**. Você encontrará a nova seção "Dados Funcionais" para preencher a Matrícula e selecionar a Unidade e o Setor.
4.  **Visualize na Lista:** Volte para a lista de usuários para ver as novas colunas "Matrícula", "Unidade" e "Setor" com as informações preenchidas.

== Frequently Asked Questions ==

= Onde eu gerencio as Unidades e Setores disponíveis? =

As Unidades e Setores são gerenciados através de seus próprios menus, localizados dentro do menu principal **Usuários** no painel administrativo do WordPress.

= Os usuários comuns podem editar esses dados? =

Não. Apenas usuários com a capacidade de editar outros usuários (geralmente, administradores) podem preencher ou modificar esses campos.

= As novas colunas na lista de usuários são ordenáveis? =

Na versão atual, as colunas são apenas para exibição e não suportam ordenação.

== Screenshots ==

1.  Menus "Unidades" e "Setores" no painel administrativo, sob o menu "Usuários".
2.  Tela de edição de perfil de usuário com a seção "Dados Funcionais" visível.
3.  Lista de usuários no painel administrativo exibindo as novas colunas: Matrícula, Unidade e Setor.

== Changelog ==

= 1.0 =
*   Versão inicial.
*   Criação dos Custom Post Types para Unidades e Setores.
*   Adição de meta fields (Matrícula, Unidade, Setor) ao perfil do usuário.
*   Exibição dos dados em colunas customizadas na lista de usuários.

== Upgrade Notice ==

= 1.0 =
Lançamento da primeira versão estável. Nenhuma ação de atualização é necessária.
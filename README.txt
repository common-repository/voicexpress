=== Voicexpress ===
Contributors: ersolucoesweb
Donate link: https://ersolucoesweb.com.br
Tags: texttospeech, voiceconverter, texttovoice
Requires at least: 6.4
Tested up to: 6.4
Stable tag: 1.2.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin para conversão de texto para audio.

== Description ==

Gere aúdios a partir dos seus posts utilizando o https://voicexpress.app/.

Alguns dados (titulo, url, imagem, nome da categoria e conteudo) do post serão enviados para a API do Voicexpress em https://voicexpress.app, para que seja feito o processamento e criação do áudio.

O plugin também utiliza nossa API para verificação de créditos disponíveis.

Termos de Uso - https://voicexpress.app/termos-de-uso

Políticas de Privacidade - https://voicexpress.app/politicas-de-privacidade


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `voicexpress.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Tem limite de caracteres? =

* Não, caso seu texto seja  muito grande (acima de 5 mil caracteres) pode ser que demore bastante para gerar o audio, mas não existe limite.

= Audio não  está sendo criado ou atualizado? =

* Caso o áudio não esteja sendo criado, uma das causas pode ser bloqueio  pelo CloudFlare caso use no seu site. Para não ocorrer bloqueio adicione uma regra no firewall para ignorar URLs que tenha a string "voicexpress_notification", para que assim a API do Voicexpress consiga notificar o site ao final da conversão do áudio.

== Screenshots ==

= screenshot-1 =

* Acesse o app

== Upgrade Notice ==

= 1.2.2 =
FIX: Correção de bug

= 1.2.1 =
FIX: Mensagem de ação em massa para forçar atualização dos áudios na listagem de posts não aparece

= 1.2.0 =
FEATURE: Ação em massa para forçar atualização dos áudios na listagem de posts

= 1.1.9 =
FIX: Correção na atualização dos créditos

= 1.1.8 =
Adição de checkbox para habilitar/desabilitar geraçãodetexto

= 1.1.7 =
Atualização do player

= 1.1.6 =
Atualização da URL dos audios

= 1.1.5 =
Correção de bug ao recuperar chave do audio

= 1.1.4 =
Correção de bugs

= 1.1.3 =
Melhorias na criação do áudio

== Changelog ==

= 1.1.9 =
FIX: Correção na atualização dos créditos

= 1.1.8 =
Adição de checkbox para habilitar/desabilitar geraçãodetexto

= 1.1.7 =
Atualização do player

= 1.1.6 =
Atualização da URL dos audios

= 1.1.5 =
Correção de bug ao recuperar chave do audio

= 1.1.4 =
* Correção de bugs

= 1.1.3 =
* Agora só é gasto créditos caso o conteúdo tenha sido alterado
* O arquivo MP3 agora é gerado assíncronamente (pode levar alguns segundos) e o player só aparece após criar o MP3
* Os MP3 são servidor a partir de uma CDN para otimizar o carregamento

= 1.1.2 =
* FIX: Correção ao gerar URL do mp3

= 1.1.1 =
* Melhorias no processo de compra de créditos
* Integração com Mercado Pago

= 1.1.0 =
* Envio assincrono dos dados para conversão

= 1.0.7 =
* Ajuste de versão

= 1.0.6 =
* Ajuste de versão

= 1.0.5 =
* FIX: sslverify alterado para false
* FIX: timeout alterado para 10

= 1.0.4 =
* FIX: ajuste nos botões

= 1.0.3 =
* Adicionado botão para comprar créditos

= 1.0.2 =
* Ajuste nas traduções

= 1.0.1 =
* Atualização dos assets

= 1.0.0 =
* Lançamento do plugin em produção
{capture name=pages}
{if $withtitle}
<div class="pages">
{if $spool->overview|@count gt $msgbypage}
{section name=pages loop=$spool->overview step=$msgbypage}
  {if $first ge $smarty.section.pages.index && $first lt $smarty.section.pages.index_next}
    <strong>{$smarty.section.pages.iteration}</strong>
  {else}
    {link group=$group first=$smarty.section.pages.index text=$smarty.section.pages.iteration}
  {/if}
{/section}
{/if}
</div>
{/if}
{/capture}

{$smarty.capture.pages|smarty:nodefaults}
<table class="bicol thread">
  <tr>
    {if $withtitle}
    <th>
      {if $spool->nextUnread()}
      <div class="menu">
        {imglink group=$group artid=$spool->nextUnread() img=next_unread alt="Message non-lu suivant"|b accesskey=u}
      </div>
      {/if}
      {"Date"|b}
    </th>
    <th>{"Sujet"|b}</th>
    <th>
      {if $protocole->canSend()}
      <div class="action">
        {imglink group=$group action=new img=post alt="Nouveau message"|b accesskey=p}
        {if $feed_active}{imglink group=$group action=$feed_format img=feed alt="Flux"|b accesskey=f}{/if}
      </div>
      {/if}
      {"Auteur"|b}
    </th>
    {else}
    <th colspan="3">
      {"Aperçu de "|b}{link group=$group text=$group}
    </th>
    {/if}
  </tr>
  {if $spool->overview|@count}
  {if $artid}{$spool->toHtml($artid, true)|smarty:nodefaults}{else}{$spool->toHtml($first)|smarty:nodefaults}{/if}
  {else}
  <tr>
    <td colspan="3">
      {"Aucun message dans ce forum"|b}
    </td>
  </tr>
  {/if}
</table>
{if $showboxlist}
{include file="banana-boxlist.inc.tpl" grouplist=$groups withstats=true}
{/if}
{$smarty.capture.pages|smarty:nodefaults}

{* vim:set et sw=2 sts=2 ts=2 enc=utf-8: *}

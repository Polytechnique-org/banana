{if $withtitle}
<div class="pages">
{if $spool->overview|@count > $msgbypage}
{section name=pages loop=$spool->overview step=$msgbypage}
  {if $first >= $smarty.section.pages.index && $first < $smarty.section.pages.index_next}
    <strong>{$smarty.section.pages.iteration}</strong>
  {else}
    {link group=$group first=$smarty.section.pages.index text=$smarty.section.pages.iteration}
  {/if}
{/section}
{/if}
</div>
{/if}
<table class="bicol thread">
  <tr>
    {if $withtitle}
    <th>
      {if $spool->nextUnread()}
      <div class="menu">
        {imglink group=$group artid=$spool->nextUnread() img=next_unread alt="Message non-lu suivant" accesskey=u}
      </div>
      {/if}
      Date
    </th>
    <th>Sujet</th>
    <th>
      {if $protocole->canSend()}
      <div class="action">
        {imglink group=$group action=new img=post alt="Nouveau message" accesskey=p}
      </div>
      {/if}
      Auteur
    </th>
    {else}
    <th colspan="3">
      {link group=$group text=$group}
    </th>
    {/if}
  </tr>
  {if $spool->overview|@count}
  {if $artid}{$spool->toHtml($artid, true)}{else}{$spool->toHtml($first)}{/if}
  {else}
  <tr>
    <td colspan="3">
      Aucun message dans ce forum
    </td>
  </tr>
  {/if}
</table>
{include file="banana-boxlist.inc.tpl" grouplist=$groups withstats=true}
{if $withtitle}
<div class="pages">
{if $spool->overview|@count > $msgbypage}
{section name=pages loop=$spool->overview step=$msgbypage}
  {if $first >= $smarty.section.pages.index && $first < $smarty.section.pages.index_next}
    <strong>{$smarty.section.pages.iteration}</strong>
  {else}
    {link group=$group first=$smarty.section.pages.index text=$smarty.section.pages.iteration}
  {/if}
{/section}
{/if}
</div>
{/if}

{* vim:set et sw=2 sts=2 ts=2: *}

{* *}
{* *}
{* *}

{if $withtabs}
<table class="cadre_a_onglet" style="width: 100%" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <ul id="onglet">
        {foreach from=$pages item=pg key=name}
          {if $name eq $page}
            <li class="actif">{$pg.text}</li>
            {assign var=current_page value=$pg}
          {else}
            <li>{if $name eq 'subscribe'}{link action=subscribe text=$pg.text}
            {elseif $name eq 'forums'}{link text=$pg.text}
            {elseif $name eq 'thread'}{link group=$group text=$group}
            {elseif $name eq 'message'}{link group=$group artid=$artid text=$pg.text}
            {else}{link page=$name text=$pg.text}
            {/if}</li>
          {/if}
        {/foreach}
      </ul>
    </td>
  </tr>
  <tr>
    <td class="conteneur_tab banana">
{else}
<div class="banana">
{/if}
      {foreach from=$errors item=error}
      <p class="error">{$error}</p>
      {/foreach}
      {if !$killed}
      {foreach from=$actions item=act}
      <p class="center" style="padding: 0; margin: 0 0 1em 0">{$act.text}</p>
      {/foreach}
      {if $page eq 'forums'}
        {include file="banana-boxlist.inc.tpl" grouplist=$groups withstats=true withfeed=$feed_active}
        {if $newgroups|@count}
        <p>{"Les nouveaux groupes suivants ont été créés depuis votre dernière visite"|b}</p>
        {include file="banana-boxlist.inc.tpl" grouplist=$newgroups withstats=true}
        {/if}
      {elseif $page eq 'subscribe'}
        {include file="banana-boxlist.inc.tpl" grouplist=$groups withsubs=true}
      {elseif $page eq 'thread'}
        {include file="banana-thread.inc.tpl" withtitle=true}
      {elseif $page eq 'message'}
        {include file="banana-message.inc.tpl"}
        {if $showthread}
        {include file="banana-thread.inc.tpl" withtitle=false}
        {/if}
      {elseif $page eq 'new'}
        {include file="banana-newmessage.inc.tpl"}
      {elseif $page eq 'cancel'}
        <p class="error">{"Voulez-vous vraiment annuler ce message ?"|b}</p>
        <form action="{url group=$group artid=$artid action=cancel}" method="post">
          <p class="center">
            <input type="submit" name="cancel" value="{"Annuler !"|b}" />
          </p>
        </form>
        {include file="banana-message.inc.tpl" noactions=true}
      {elseif $current_page.template}
        {include file=$current_page.template}
      {/if}
      {/if}
{if $withtabs}
    </td>
  </tr>
</table>
{else}
</div>
{/if}

{* vim:set et sw=2 sts=2 ts=2 enc=utf-8: *}

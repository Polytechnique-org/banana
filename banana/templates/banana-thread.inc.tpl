{capture name=pages}
{if $withtitle}
<div class="pages">
{if $spool->roots|@count gt $msgbypage}
{section name=pages loop=$spool->roots step=$msgbypage}
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
      {assign var=nextUnread value=$spool->nextUnread()}
      {if $nextUnread}
      <div class="menu">
        {imglink group=$group artid=$nextUnread img=next_unread alt="Message non-lu suivant"|b accesskey=u}
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
    <th colspan="3">{"En discussion sur "|b}{link group=$group text=$group}...</th>
    {/if}
  </tr>
  {if $spool->roots|@count}
  {section name=threads loop=$spool->roots step=1 start=$spool->start() max=$spool->context()}
  {assign var=overview value=$spool->roots[$smarty.section.threads.index]}
  {assign var=id value=$overview->id}
  {cycle assign=class values="impair,pair"}
  <tr class="{$class} {if $overview->descunread}new{/if}">
    <td class="date">{$spool->formatDate($overview)}</td>
    <td class="subj">{$spool->formatSubject($overview)|smarty:nodefaults}</td>
    <td class="from">{$spool->formatFrom($overview)|smarty:nodefaults}</td>
  </tr>
  {if !$artid && $spool->nextPost($id)}
  <tr class="{$class}">
    <td colspan="3" class="thread_tree">{$spool->getTree($id)|smarty:nodefaults}</td>
  </tr>
  {/if}
  {/section}
  {else}
  <tr>
    <td colspan="3">
      {"Aucun message dans ce forum"|b}
    </td>
  </tr>
  {/if}
</table>
{$smarty.capture.pages|smarty:nodefaults}
{if $showboxlist}
<br />
{include file="banana-boxlist.inc.tpl" grouplist=$groups withstats=true}
{/if}

{* vim:set et sw=2 sts=2 ts=2 enc=utf-8: *}

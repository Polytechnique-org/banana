<table class="bicol message">
  <tr>
    <th colspan="3" class="subject">
      {if !$noactions}
      <div class="menu">
        {if $spool->nextUnread($artid)}
        {imglink group=$group artid=$spool->nextUnread($artid) img=next_unread alt="Message non-lu suivant" accesskey=u}{/if}
        {if $spool->prevPost($artid)}
        {imglink group=$group artid=$spool->prevPost($artid) img=prev alt="Message précédent" accesskey=a}{/if}
        {if $spool->nextPost($artid)}
        {imglink group=$group artid=$spool->nextPost($artid) img=next alt="Message suivant" accesskey=z}{/if}
        {if $spool->prevThread($artid)}
        {imglink group=$group artid=$spool->prevThread($artid) img=prev_thread alt="Discussion précédente" accesskey=q}{/if}
        {if $spool->nextThread($artid)}
        {imglink group=$group artid=$spool->nextThread($artid) img=next_thread alt="Discussion suivante" accesskey=s}{/if}
      </div>
      <div class="action">
        {if $message->canSend()}
        {imglink group=$group action="new" img=post alt="Nouveau messasge" accesskey=p}
        {imglink group=$group artid=$artid action="new" img=reply alt="Répondre" accesskey=r}
        {/if}
        {if $message->canCancel()}
        {imglink group=$group artid=$artid action="cancel" img=cancel alt="Annuler" accesskey=c}
        {/if}
      </div>
      {/if}
      {$message->translateHeaderValue('subject')}
    </th>
  </tr>
  {foreach from=$headers name=headers item=hdr}
  <tr class="pair">
    <td class="hdr">{$message->translateHeaderName($hdr)}</td>
    <td>{$message->translateHeaderValue($hdr)}</td>
    {if $smarty.foreach.headers.first}
    <td class="xface" rowspan="{$headers|@count}">
      {if $message->hasXFace()}
      <img src="{url group=$group artid=$artid part="xface"}" alt="[ X-Face ]" />
      {/if}
    </td>
    {/if}
  </tr>
  {/foreach}
  {assign var=files value=$message->getAttachments()}
  {if $files|@count}
  <tr class="pair">
    <td class="hdr">Fichiers joints</td>
    <td colspan="2">
      {foreach from=$files item=file name=attachs}
      {$file->getFilename()|htmlentities}
      {imglink img=save alt="Enregistrer" group=$group artid=$artid part=$file->getFilename()}{if !$smarty.foreach.attachs.last}, {/if}
      {/foreach}
    </td>
  </tr>
  {/if}
  <tr>
    <td colspan="3" class="body">
      {$message->getFormattedBody()}
    </td>
  </tr>
</table>

{* vim:set et sw=2 sts=2 ts=2: *}

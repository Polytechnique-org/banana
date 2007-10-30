<table class="bicol message">
  <tr>
    <th colspan="3" class="subject">
      {if !$noactions}
      <div class="menu">
        {assign var=nextUnread value=$spool->nextUnread($artid)}
        {assign var=prevPost value=$spool->prevPost($artid)}
        {assign var=nextPost value=$spool->nextPost($artid)}
        {assign var=prevThread value=$spool->prevThread($artid)}
        {assign var=nextThread value=$spool->nextThread($artid)}
        {if $nextUnread}{imglink group=$group artid=$nextUnread img=next_unread alt="Message non-lu suivant"|b accesskey=u}{/if}
        {if $prevPost}{imglink group=$group artid=$prevPost img=prev alt="Message précédent"|b accesskey=a}{/if}
        {if $nextPost}{imglink group=$group artid=$nextPost img=next alt="Message suivant"|b accesskey=z}{/if}
        {if $prevThread}{imglink group=$group artid=$prevThread img=prev_thread alt="Discussion précédente"|b accesskey=q}{/if}
        {if $nextThread}{imglink group=$group artid=$nextThread img=next_thread alt="Discussion suivante"|b accesskey=s}{/if}
      </div>
      <div class="action">
        {if $message->canSend()}
        {imglink group=$group action="new" img=post alt="Nouveau message"|b accesskey=p}
        {imglink group=$group artid=$artid action="new" img=reply alt="Répondre"|b accesskey=r}
        {/if}
        {if $message->canCancel()}
        {imglink group=$group artid=$artid action="cancel" img=cancel alt="Annuler"|b accesskey=c}
        {/if}
      </div>
      {/if}
      {$message->translateHeaderValue('subject')|smarty:nodefaults}
    </th>
  </tr>
  {foreach from=$headers name=headers item=hdr}
  <tr class="pair">
    <td class="hdr">{$message->translateHeaderName($hdr)}</td>
    <td>{$message->translateHeaderValue($hdr)|smarty:nodefaults}</td>
    {if $smarty.foreach.headers.first}
    <td class="xface" rowspan="{$headers|@count}">
      {if $message->hasXFace()}
      <img src="{url group=$group artid=$artid part="xface"}" style="width: 48px" alt="[ X-Face ]" />
      {/if}
    </td>
    {/if}
  </tr>
  {/foreach}
  {assign var=files value=$message->getAttachments()}
  {if $files|@count}
  <tr class="pair">
    <td class="hdr">{"Fichiers joints"|b}</td>
    <td colspan="2">
      {foreach from=$files item=file name=attachs}
      {imglink img=save alt="Enregistrer"|b group=$group artid=$artid part=$file->getFilename() text=$file->getFilename()}{if !$smarty.foreach.attachs.last}, {/if}
      {/foreach}
    </td>
  </tr>
  {/if}
  {assign var=signature value=$message->getSignature()}
  {if $signature && $signature.key.id}
  <tr class="pair">
    <td class="hdr">{"Signature"|b}</td>
    <td colspan="2">
      {if $signature.verify && $signature.certified}
      {img img=accept alt="Signature valide par une clé de confiance"|b}
      {elseif $signature.verify}
      {img img=error alt="Signature valide par une clé non vérifiée"|b}
      {else}
      {img img=exclamation alt="Signature non valide"|b}
      {/if}
      <strong>
        {if $signature.verify}<span class="ok">{"Valide"|b}...</span>
        {else}<span class="erreur">{"Non valide"|b}...</span>{/if}
      </strong>&nbsp;
      {"Message signé par la clé"|b} {$signature.key.format}:{$signature.key.id}
      {if $signature.certified}
        (<span class="ok">{"identité vérifiée"|b}</span>)
      {else}
        (<span class="erreur">{"non vérifiée"|b}</span>&nbsp;: {$signature.certification_error})
      {/if}
    </td>
  </tr>
  {/if}
  {assign var=alter value=$message->getAlternatives()}
  {if $alter|@count}
  <tr class="pair">
    <td class="hdr">{"Versions"|b}</td>
    <td colspan="2">
      {foreach from=$alter key=ctype item=text name=alter}
      {if $type eq $ctype}
      {$text}
      {if $extimages}[{link group=$group artid=$artid part=$type action=showext text="Afficher les images externes"|b}]{/if}
      {else}
      {link group=$group artid=$artid part=$ctype text=$text}
      {/if}
      {if !$smarty.foreach.alter.last}&nbsp;&bull;&nbsp;{/if}
      {/foreach}
    </td>
  </tr>
  {/if}
  <tr>
    <td colspan="3" class="body">
      {$body|banana_utf8entities|smarty:nodefaults}
    </td>
  </tr>
  {if $spool && ($nextPost || $prevPost)}
  <tr class="pair">
    <th colspan="3">
      <strong>{"Naviguer dans la discussion..."|b}</strong>
    </th>
  </tr>
  <tr class="pair">
    <td colspan="3" class="thread_tree">{$spool->getTree($artid)|smarty:nodefaults}</td>
  </tr>
  {/if}
</table>

{* vim:set et sw=2 sts=2 ts=2 enc=utf-8: *}

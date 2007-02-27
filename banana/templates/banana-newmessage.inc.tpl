<form {if $can_attach}enctype="multipart/form-data"{/if} action="{url group=$group artid=$artid action=new}" method="post" accept-charset="utf-8">
  <table class="bicol">
    <tr>
      <th colspan="2">{"Composer un nouveau message"|b}</th>
    </tr>
    {foreach from=$headers key=header item=values}
    <tr class="pair">
      <td>
        {$values.name|htmlentities}
      </td>
      <td>
        {if $values.fixed}
        {$values.fixed|htmlentities}
        {else}
        <input type="text" name="{$header}" value="{$values.user|default:$smarty.request.$header}" size="50" />
        {/if}
      </td>
    </tr>
    {/foreach}
    <tr>
      <td colspan="2" class="center">
        <textarea name="body" cols="74" rows="16">{$body|default:$smarty.request.body}</textarea>
      </td>
    </tr>
    {if $can_attach}
    <tr>
      <td>{"Fichier joint"|b}</td>
      <td>
        {if $maxfilesize}
        <input type="hidden" name="MAX_FILE_SIZE" value="{$maxfilesize}" />
        {/if}
        <input type="file" name="attachment" size="40" />
      </td>
    </tr>
    {/if}
    <tr>
      <td colspan="2" class="center">
        <input type="submit" name="sendmessage" value="{"Envoyer le message"|b}" />
      </td>
    </tr>
  </table>
</form>

{* vim:set et sts=2 ts=2 sw=2 enc=utf-8: *}

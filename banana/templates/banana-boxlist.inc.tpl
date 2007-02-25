{if $grouplist|@count}
{if $withsubs}
<form action="{url action=subscribe}" method="post">
<p style="text-align: center">
  <input type="submit" name="validsubs" value="{"Valider"|b}" />
</p>
{/if}
<table class="bicol">
  <tr>
    {if $withsubs}
    <th></th>
    {/if}
    {if $withstats}
    <th>{"Total"|b}</th>
    <th>{"Nouveaux"|b}</th>
    {/if}
    <th>{"Nom"|b}</th>
    <th>
      {if $withfeed}
      <div class="action">
        {imglink action=$feed_format img=feed alt="Flux"|b accesskey=f}
      </div>
      {/if}
      {"Description"|b}
    </th>
  </tr>
  {foreach from=$grouplist key=name item=grp}
  <tr class="{cycle values="impair,pair"}">
    {if $withsubs}
    <td>
      <input type="checkbox" name="subscribe[{$name}]" {if in_array($name, $profile.subscribe)}checked="checked"{/if} />
    </td>
    {/if}
    {if $withstats}
    <td style="text-align: center">{if $grp.msgnum eq 0}-{else}{$grp.msgnum}{/if}</td>
    <td style="text-align: center">{if $grp.unread eq 0}-{else}{$grp.unread}{/if}</td>
    {/if}
    <td class="grp">{link group=$name text=$name}</td>
    <td class="dsc">{$grp.desc}</td>
  </tr>
  {/foreach}
</table>
{if $withsubs}
<p style="text-align: center">
  <input type="submit" name="validsubs" value="{"Valider"|b}" />
</p>
</form>
{/if}
{/if}

{* vim:set et sw=2 sts=2 ts=2 enc=utf-8: *}

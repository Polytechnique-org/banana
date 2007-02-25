<?xml version="1.0"?>
<rss version="2.0">
  <channel>
    <title>{$title_prefix}{$feed->group}</title>
    <language>{$language}</language>
    <link>{url group=$group}</link>
    <description>{$feed->description}</description>
    {foreach from=$feed->messages key=id item=message}
    <item>
      <title><![CDATA[{$message.title}]]></title>
      <guid isPermaLink="false">{$id}</guid>
      <link>{$message.link}</link>
      <description><![CDATA[{$message.body}]]></description>
      <author>{$message.author}</author>
      <pubDate>{$message.date|rss_date}</pubDate>
    </item>
    {/foreach}
  </channel>
</rss>
{* vim:set et sw=2 sts=2 ts=2 enc=utf-8: *}

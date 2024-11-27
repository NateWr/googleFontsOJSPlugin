{**
 * Settings form to manage Google Fonts
 *}
<tab id="google-fonts" label="Google Fonts">
  <div class="google-fonts-settings">
    <h1>Added Fonts</h1>
    <ul>
      {foreach from=$googleFontsEnabled item="font"}
        <li>
          <a
            href="https://fonts.google.com/specimen/{$font->family|escape|replace:' ':'+'}"
            target="_blank"
          >
            {$font->family}
          </a>
          <form
            action={url page="google-font" op="remove"}
            method="post"
          >
            {csrf}
            <input type="hidden" name="font" value="{$font->id}">
            <button type="submit">
              Remove
            </button>
          </form>
        </li>
      {/foreach}
    </ul>
    {if $googleFontsOptions|@count}
      <form
        action={url page="google-font" op="add"}
        method="post"
      >
        {csrf}
        <select name="font">
          {foreach from=$googleFontsOptions item="googleFontsOption"}
            <option value="{$googleFontsOption->id|escape}">
              {$googleFontsOption->family|escape}
            </option>
          {/foreach}
        </select>
        <button type="submit">
          Add Font
        </button>
      </form>
    {elseif $googleFontsError}
      <div class="pkpNotification pkpNotification--warning">
        {$googleFontsError}
      </div>
    {/if}
  </div>
</tab>
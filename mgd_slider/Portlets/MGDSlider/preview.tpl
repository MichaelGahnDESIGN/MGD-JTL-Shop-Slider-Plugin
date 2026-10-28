{* Vorschau im OPC-Editor - Platzhalter, echte Vorschau später *}
<div class="opc-MGDSlider-preview" style="padding:24px;border:1px dashed #999;text-align:center;">
    <strong>MGD Slider</strong><br>
    {if $instance->getProperty('sliderId') != ''}
        Slider #{$instance->getProperty('sliderId')|escape:'html'}
    {else}
        noch kein Slider gewählt
    {/if}
</div>

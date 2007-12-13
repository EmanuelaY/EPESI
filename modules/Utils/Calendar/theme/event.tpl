{php}
	load_js('modules/Utils/Calendar/theme/event.js');
{/php}

{$open}

<span id="Utils_Calendar__event">

    <span class="event_menu" id="event_menu_{$event_id}" style="display: none;">
        <!-- SHADIW BEGIN -->
        <div class="layer" style="padding: 10px; width: 86px;">
        	<div class="content_shadow">
        <!-- -->
        
        <span id="event_menu_content"><img {$tip_tag_attrs} border=0 src="{$theme_dir}/Utils_Calendar__view.png"><img {$tip_tag_attrs} border=0 src="{$theme_dir}/Utils_Calendar__edit.png"><img {$tip_tag_attrs} border=0 src="{$theme_dir}/Utils_Calendar__delete.png"><img {$tip_tag_attrs} border=0 src="{$theme_dir}/Utils_Calendar__select.png"></span>
        
        <!-- SHADOW END -->
 		</div>
		<div class="shadow-top">
			<div class="left"></div>
			<div class="center"></div>
			<div class="right"></div>
		</div>
		<div class="shadow-middle">
			<div class="left"></div>
			<div class="right"></div>
		</div>
		<div class="shadow-bottom">
			<div class="left"></div>
			<div class="center"></div>
			<div class="right"></div>
		</div>
    	</div>
        <!-- -->
    </span>
    
    <div class="row">
        <span id="event_grab" class="{$handle_class}"><img {$tip_tag_attrs} border=0 src="{$theme_dir}/Utils_Calendar__grab.png"></span>
        <span id="event_time">time {$event_id}</span>
        <span id="event_info"><img {$tip_tag_attrs} src="{$theme_dir}/Utils_Calendar__info.png" onClick="event_menu('{$event_id}')" width="14" height="14" border="0"></span>
    </div>
     <div class="row">
        <span id="event_title" {$view_action}>{$title}</span>
    </div>
</span>

{$close}

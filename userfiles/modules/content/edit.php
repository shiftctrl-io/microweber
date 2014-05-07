<?php
 
  only_admin_access();

  $rand = uniqid();
  $data = false;
  $just_saved = false;
  $is_new_content = false;
  $is_current = false;
  $is_live_edit = false;
  if(!isset($is_quick)){
      $is_quick=false;
  }
  
  if(isset($params['live_edit'])){
    $is_live_edit = $params['live_edit'];
  } elseif(isset($params['from_live_edit'])){
   $is_live_edit = $params['from_live_edit'];
  }

  if(isset($params['quick_edit'])){
    $is_quick = $params['quick_edit'];
  }
  if($is_live_edit == true){
	   $is_quick = false;
  }
  
  if(isset($params['just-saved'])){
    $just_saved = $params['just-saved'];
  }
  if(isset($params['is-current'])){
    $is_current = $params['is-current'];
  }
  if(isset($params['page-id'])){
    $data = get_content_by_id(intval($params["page-id"]));
  }
  if(isset($params['content-id'])){
    $data = get_content_by_id(intval($params["content-id"]));
  }
  $recommended_parent = false;
  if(isset($params['recommended_parent']) and $params['recommended_parent'] != false){
    $recommended_parent = $params['recommended_parent'];
  }
  $categories_active_ids =false;
  $title_placeholder = false;

  /* FILLING UP EMPTY CONTENT WITH DATA */
  if($data == false or empty($data )){
     $is_new_content = true;
     include('_empty_content_data.php');
  }
  
  if(isset($params['add-to-menu'])){
    $data['add_to_menu'] = (($params["add-to-menu"]));
  }
  
  

/* END OF FILLING UP EMPTY CONTENT  */
 

/* SETTING PARENT AND ACTIVE CATEGORY */
$forced_parent = false;
if(intval($data['id']) == 0 and intval($data['parent']) == 0 and isset($params['parent-category-id']) and $params['parent-category-id'] != 0 and !isset($params['parent-page-id'])){
      $cat_page = get_page_for_category($params['parent-category-id']);
	  if(is_array($cat_page) and isset($cat_page['id'])){
		$forced_parent = $params['parent-page-id'] = $cat_page['id'];
	  }
}

if(intval($data['id']) == 0 and intval($data['parent']) == 0 and isset($params['parent-page-id'])){
    $data['parent'] = $params['parent-page-id'];
    if(isset($params['subtype']) and $params['subtype'] == 'product'){
        $parent_content = get_content_by_id($params['parent-page-id']);
       // if(!isset($parent_content['is_shop']) or $parent_content['is_shop'] != 'y'){
			  
           // $data['parent'] = 0;
       // }
    }
    if(isset($params['parent-category-id']) and $params['parent-category-id'] != 0){
        $categories_active_ids =$params['parent-category-id'];
    }
}
else if(intval($data['id']) != 0){
    $categories  =get_categories_for_content($data['id']);
   if(is_array($categories)){
	 $c = array();
	 foreach($categories as $category){
		 $c[] = $category['id'];
	 }
 	 $categories_active_ids = implode(',',$c);
    }

}
/* END OF SETTING PARENT AND ACTIVE CATEGORY  */



/* SETTING PARENT AND CREATING DEFAULT BLOG OR SHOP IF THEY DONT EXIST */
if(intval($data['id']) == 0 and intval($data['parent']) == 0){
	$parent_content_params = array();
	$parent_content_params['subtype'] = 'dynamic';
	$parent_content_params['content_type'] = 'page';
	$parent_content_params['limit'] = 1;
	$parent_content_params['one'] = 1;
	$parent_content_params['fields'] = 'id';
//	$parent_content_params['is_active'] = 'y';
	$parent_content_params['order_by'] = 'posted_on desc, updated_on desc';
	 
	if(isset($params['subtype']) and $params['subtype'] == 'post'){
		$parent_content_params['is_shop'] = 'n';
		$parent_content_params['is_home'] = 'n';
	    $parent_content = get_content($parent_content_params);
		 
		 if(isset($parent_content['id'])){
			 $data['parent'] = $parent_content['id'];
		 } else {
			  mw('content')->create_default_content('blog');
			  $parent_content_params['no_cache'] = true;
			  $parent_content = get_content($parent_content_params);
		 }
	} elseif(isset($params['subtype']) and $params['subtype'] == 'product'){
		$parent_content_params['is_shop'] = 'y';
	    $parent_content = get_content($parent_content_params);
		 if(isset($parent_content['id'])){
			 $data['parent'] = $parent_content['id'];
		 } else {
			  mw('content')->create_default_content('shop');
			  $parent_content_params['no_cache'] = true;
			  $parent_content = get_content($parent_content_params);
		 }
	} 
	if(isset($parent_content) and isset($parent_content['id'])){
			 $data['parent'] = $parent_content['id'];
	 } 

} elseif($forced_parent == false and (intval($data['id']) == 0 and intval($data['parent']) != 0) and isset($data['subtype']) and $data['subtype'] == 'product'){
	 
	 //if we are adding product in a page that is not a shop
	 $parent_shop_check =  get_content_by_id($data['parent']);
	 if(!isset($parent_shop_check['is_shop']) or $parent_shop_check['is_shop'] != 'y'){
		 $parent_content_shop = get_content('order_by=updated_on desc&one=true&is_shop=y');
		  if(isset($parent_content_shop['id'])){
			 $data['parent'] = $parent_content_shop['id'];
		 }
	 }
	 
} elseif($forced_parent == false and (intval($data['id']) == 0 and intval($data['parent']) != 0) and isset($data['subtype']) and $data['subtype'] == 'post'){
	 
	 //if we are adding product in a page that is not a shop
	 $parent_shop_check =  get_content_by_id($data['parent']);
	
	 if(!isset($parent_shop_check['subtype']) or $parent_shop_check['subtype'] != 'dynamic'){
		 $parent_content_shop = get_content('order_by=updated_on desc&one=true&subtype=dynamic&is_shop=n');
		  if(isset($parent_content_shop['id'])){
			  $data['parent'] = $parent_content_shop['id'];
		 }
	 }
	 
}
 
/* END OF SETTING PARENT AND CREATING DEFAULT BLOG OR SHOP IF THEY DONT EXIST */

 $module_id = $params['id'];
 
 
?>
<?php if($just_saved!=false) : ?>

<?php endif; ?>
<?php  
$edit_page_info = $data;;
include __DIR__ . DS . 'admin_toolbar.php'; ?>   

<div id="post-states-tip" style="display: none">
    <div>
        <span data-val="n" class="<?php if($data['is_active'] == 'n'): ?> active<?php endif; ?>"><span class="mw-icon-disabled"></span>
        <?php _e("Unpublished"); ?>
        </span><span data-val="y" class="<?php if($data['is_active'] != 'n'): ?> active<?php endif; ?>"><span class="mw-icon-check"></span>
        <?php _e("Published"); ?>
        </span>
        <span class="mw-uibtn-important"><span class="mw-icon-bin"></span>Move to trash</span>
    </div>
</div>

<form method="post" <?php if($just_saved!=false) : ?> style="display:none;" <?php endif; ?> class="mw_admin_edit_content_form" action="<?php print site_url(); ?>api/save_content_admin" id="quickform-<?php print $rand; ?>">
  <input type="hidden" name="id" id="mw-content-id-value-<?php print $rand; ?>"  value="<?php print $data['id']; ?>" />
  <input type="hidden" name="subtype" id="mw-content-subtype-value-<?php print $rand; ?>"   value="<?php print $data['subtype']; ?>" />
  <input type="hidden" name="content_type" id="mw-content-type-value-<?php print $rand; ?>"   value="<?php print $data['content_type']; ?>" />
  <input type="hidden" name="parent"  id="mw-parent-page-value-<?php print $rand; ?>" value="<?php print $data['parent']; ?>" class="" />
  
  
   <input type="hidden" name="layout_file"  id="mw-layout-file-value-<?php print $rand; ?>" value="<?php print $data['layout_file']; ?>"   />
   <input type="hidden" name="active_site_template"  id="mw-active-template-value-<?php print $rand; ?>" value="<?php print $data['active_site_template']; ?>"   />
  
   <div class="mw-ui-field-holder">
<div class="mw-ui-btn-nav pull-right" id="un-or-published">

<?php /*            <span data-val="n" class="mw-ui-btn <?php if($data['is_active'] == 'n'): ?> active<?php endif; ?>"><span class="mw-icon-disabled"></span>
            <?php _e("Unpublished"); ?>
            </span><span data-val="y" class="mw-ui-btn<?php if($data['is_active'] != 'n'): ?> active<?php endif; ?>"><span class="mw-icon-check"></span>
            <?php _e("Published"); ?>
            </span>
*/ ?>

            <?php if($data['is_active'] == 'n'){ ?>
            <span
             onmouseenter="mw.tooltip({position:'top-center',content:mwd.getElementById('post-states-tip').innerHTML, element:this})"
            data-val="n" class="mw-ui-btn <?php if($data['is_active'] == 'n'): ?> active<?php endif; ?>"><span class="mw-icon-disabled"></span>
             <?php _e("Unpublished"); ?>
            </span>
            <?php } else{  ?>

            <span
             onmouseenter="mw.tooltip({position:'top-center',content:mwd.getElementById('post-states-tip').innerHTML, element:this})"
            data-val="y" class="mw-ui-btn<?php if($data['is_active'] != 'n'): ?> active<?php endif; ?>"><span class="mw-icon-check" ></span>
            <?php _e("Published"); ?>
            </span>

        <?php    } ?>

            <?php if($is_live_edit == false) : ?>

                    <button type="button" class="mw-ui-btn mw-ui-btn-info" onclick="mw.edit_content.handle_form_submit(true);" data-text="<?php _e("Go Live Edit"); ?>">
                    <span class="mw-icon-live"></span><?php _e("Go Live Edit"); ?>
                    </button>
                    <button type="submit" class="mw-ui-btn mw-ui-btn-notification ">
                    <?php _e("Save"); ?>
                    </button>
                    <?php else: ?>
                    <?php if($data['id'] == 0): ?>
                    <button type="submit" class="mw-ui-btn mw-ui-btn-info" onclick="mw.edit_content.handle_form_submit(true);" data-text="<?php _e("Go Live Edit"); ?>">
                    <span class="mw-icon-live"></span><?php _e("Go Live Edit"); ?>
                    </button>
                    <?php else: ?>
                    <button type="button" class="mw-ui-btn mw-ui-btn-info" onclick="mw.edit_content.handle_form_submit(true);" data-text="<?php _e("Go Live Edit"); ?>">
                    <span class="mw-icon-live"></span><?php _e("Go Live Edit"); ?>
                    </button>
                    <?php endif; ?>
                    <button type="submit" class="mw-ui-btn mw-ui-btn-notification ">
                    <?php _e("Save"); ?>
                    </button>
            <?php endif; ?>
        </div>

      <input
            type="hidden"
            id="content-title-field-master"
            name="title"
            onkeyup="slugFromTitle();"
            placeholder="<?php print $title_placeholder; ?>"
            value="<?php print $data['title']; ?>" />
      <input type="hidden" name="is_active" id="is_post_active" value="<?php print $data['is_active']; ?>" />
<div class="edit-post-url">

        <div class="mw-ui-row">
            <div class="mw-ui-col" id="slug-base-url-column">
                <span class="view-post-site-url" id="slug-base-url"><?php print site_url(); ?></span>
                <script>$(mwd).ready(function(){mwd.getElementById('slug-base-url-column').style.width = mwd.getElementById('slug-base-url').offsetWidth + 'px';});</script>
            </div>
            <div class="mw-ui-col">
               <span class="view-post-slug active" onclick="mw.slug.toggleEdit()"><?php print ($data['url'])?></span>
               <input name="content_url" id="edit-content-url" class="mw-ui-invisible-field mw-ui-field-small w100 edit-post-slug"  onblur="mw.slug.toggleEdit();mw.slug.setVal(this);slugEdited=true;" type="text" value="<?php print ($data['url'])?>" />
            </div>
        </div>

    </div>
      <script>
         var piblished_nav = mwd.querySelectorAll('#un-or-published');
         var piblished_nav = piblished_nav[piblished_nav.length-1];
         mw.ui.btn.radionav(piblished_nav, 'span[data-val]');
         $(piblished_nav.getElementsByTagName('span')).bind("click", function(){
              if($(this).hasClass("active")){
                  mw.$("#is_post_active").val($(this).dataset("val"));
              }
         });
         slugEdited = false;
         slugFromTitle = function(){
            var slugField = mwd.getElementById('edit-content-url');
            var titlefield = mwd.getElementById('content-title-field');
            if(slugEdited === false){
                var slug = mw.slug.create(titlefield.value)
                mw.$('.view-post-slug').html(slug);
                mw.$('#edit-content-url').val(slug);
            }
         }
      </script>
  </div>

  
   <div class="mw-ui-btn-nav" id="quick-add-post-options">
    <span class="mw-ui-btn"><span class="ico itabpic"></span><span>
      <?php _e("Picture Gallery"); ?>
      </span></span>
    <?php if($data['content_type'] == 'page'): ?>
    <span class="mw-ui-btn"><span class="ico itabaddtonav"></span><span>
      <?php _e('Add to navigation menu'); ?>
      </span> </span>
    <?php endif; ?>
    <?php  if(trim($data['subtype']) == 'product'): ?>
    <span class="mw-ui-btn"><span class="ico itabprice"></span><span>
      <?php _e("Price & Fields"); ?>
      </span></span>
    <span class="mw-ui-btn"><span class="ico itabtruck"></span><span>
      <?php _e("Shipping & Options"); ?>
      </span></span>
    <?php else: ?>
    <span class="mw-ui-btn"><span class="ico itabcustoms"></span><span>
      <?php _e("Custom Fields"); ?>
      </span></span>
    <?php endif; ?>
    <span class="mw-ui-btn"><span class="ico itabadvanced"></span><span>
      <?php _e("Advanced"); ?>
      </span></span>
      
     <?php if($data['content_type'] == 'page'):  ?> 
        <span class="mw-ui-btn"><span class="ico itabadvanced"></span><span>
      <?php _e("Template"); ?>
      </span></span>
       <?php endif; ?>
        <?php event_trigger('mw_admin_edit_page_tabs_nav', $data); ?>
  
      
      
  </div>
  <div class="mw-ui-box mw-ui-box-content quick-add-post-options-item" id="quick-add-gallery-items">
    <module type="pictures/admin" for="content" for-id=<?php print $data['id']; ?> />
    <?php event_trigger('mw_admin_edit_page_after_pictures', $data); ?>
            <?php event_trigger('mw_admin_edit_page_tab_1', $data); ?>

  </div>
  <?php if($data['content_type'] == 'page'): ?>
  <div class="mw-ui-box mw-ui-box-content quick-add-post-options-item">
    <?php event_trigger('mw_edit_page_admin_menus', $data); ?>
    <?php event_trigger('mw_admin_edit_page_after_menus', $data); ?>
                <?php event_trigger('mw_admin_edit_page_tab_2', $data); ?>

  </div>
  <?php endif; ?>
  <div class="mw-ui-box mw-ui-box-content quick-add-post-options-item">
    <module
                    type="custom_fields/admin"
                    <?php if( trim($data['subtype']) == 'product' ): ?> default-fields="price" <?php endif; ?>
                    content-id="<?php print $data['id'] ?>"
                    suggest-from-related="true"
                    list-preview="true"
                    id="fields_for_post_<?php print $rand; ?>" 	 />
                    
                    
                    

              <?php event_trigger('mw_admin_edit_page_tab_3', $data); ?>

  </div>
  <?php  if(trim($data['subtype']) == 'product'): ?>
  <div class="mw-ui-box mw-ui-box-content quick-add-post-options-item">
    <?php event_trigger('mw_edit_product_admin', $data); ?>
  </div>
  <?php endif; ?>
  <div class="mw-ui-box mw-ui-box-content quick-add-post-options-item" id="quick-add-post-options-item-advanced">

   <?php event_trigger('mw_admin_edit_page_tab_4', $data); ?>
   
   
   
     <div class="mw-ui-field-holder">

    <?php if($data['content_type'] == 'page'){ ?>
    <div class="quick-parent-selector">
      <module type="content/selector" no-parent-title="No parent page" field-name="parent_id_selector" change-field="parent" selected-id="<?php print $data['parent']; ?>"  remove_ids="<?php print $data['id']; ?>" recommended-id="<?php print $recommended_parent; ?>"   />
    </div>
    <?php } ?>
  </div>
  <?php if($data['content_type'] != 'page' and $data['subtype'] != 'category'): ?>
  <div class="mw-ui-field-holder" style="padding-top: 0">
    <div class="mw-ui-field mw-tag-selector mw-ui-field-dropdown mw-ui-field-full" id="mw-post-added-<?php print $rand; ?>">
      <input type="text" class="mw-ui-invisible-field" placeholder="<?php _e("Click here to add to categories and pages"); ?>." style="width: 280px;" id="quick-tag-field" />
    </div>
    <div class="mw-ui-category-selector mw-ui-category-selector-abs mw-tree mw-tree-selector" id="mw-category-selector-<?php print $rand; ?>" >
      <?php if($data['content_type'] != 'page' and $data['subtype'] != 'category'): ?>
      <module
                    type="categories/selector"
                    for="content"
        			active_ids="<?php print $data['parent']; ?>"
        			subtype="<?php print $data['subtype']; ?>"
        			categories_active_ids="<?php print $categories_active_ids; ?>"
        			for-id="<?php print $data['id']; ?>" />
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  
    <module type="content/advanced_settings" content-id="<?php print $data['id']; ?>"  content-type="<?php print $data['content_type']; ?>" subtype="<?php print $data['subtype']; ?>"    />
  </div>
  
  <?php if($data['content_type'] == 'page'):  ?>
    <div class="mw-ui-box mw-ui-box-content quick-add-post-options-item quick-add-content-template" id="quick-add-post-options-item-template">

  <module type="content/layout_selector" id="mw-quick-add-choose-layout" autoload="yes" template-selector-position="bottom" content-id="<?php print $data['id']; ?>" inherit_from="<?php print $data['parent']; ?>" />
  
  
    </div>
  <?php endif; ?>
  

  
  
  
     <?php event_trigger('mw_admin_edit_page_tabs_end', $data); ?>
  
  
  
  
  <?php // if($data['subtype'] == 'static' or $data['subtype'] == 'post' or $data['subtype'] == 'product'): ?>
    <?php  if(isset($data['subtype']) and $data['subtype'] != 'dynamic'): ?>
 
  <div class="mw-ui-field-holder" id="mw-edit-page-editor-holder">
    <div id="quick_content_<?php print $rand ?>"></div>
  </div>
 
  <?php   endif; ?>
  
<?php  if(isset($data['subtype']) and $data['subtype'] == 'dynamic'
or ($data['id'] == 0 and isset($data['content_type']) and $data['content_type'] == 'page')

): ?>
 <script>
     mw.$("#quick-add-post-options-item-template").show();
	  mw.$("#mw-edit-page-editor-holder").hide();
	  </script>
  <?php   endif; ?>

  <hr class="hr2">

 

  <?php event_trigger('mw_admin_edit_page_footer', $data); ?>
</form>
<div class="quick_done_alert" style="display: none" id="post-added-alert-<?php print $rand; ?>">
  <div class="quick-post-done">
    <h2>Well done, you have saved your changes. </h2>
    <label class="mw-ui-label"><small>Go to see them at this link</small></label>
    <div class="vSpace"></div>
    <a target="_top" class="quick-post-done-link" href="<?php print content_link($data['id']); ?>?editmode=y"><?php print content_link($data['id']); ?></a>
    <div class="vSpace"></div>
    <label class="mw-ui-label"><small>Or choose an action below</small></label>
    <div class="vSpace"></div>
    <a href="javascript:;" class="mw-ui-btn" onclick="mw.edit_content.close_alert();">Continue editing</a> <a href="javascript:;" class="mw-ui-btn mw-ui-btn-green" onclick="mw.edit_content.create_new();">Create New</a> </div>
</div>
<script>
    mw.require("content.js");
    mw.require("files.js");
</script> 
<script>
/* FUNCTIONS */



mw.edit_content = {};

mw.edit_content.saving = false;



mw.edit_content.create_new = function(){
   mw.$('#<?php print $module_id ?>').attr("content-id", "0");
   mw.$('#<?php print $module_id ?>').removeAttr("just-saved");

   mw.reload_module('#<?php print $module_id ?>');
};

mw.edit_content.close_alert = function(){
   	 mw.$('#quickform-<?php print $rand; ?>').show();
	 mw.$('#post-added-alert-<?php print $rand; ?>').hide();

};

 

mw.edit_content.load_editor  =  function(element_id){
	 var element_id =  element_id || 'quick_content_<?php print $rand ?>';
	 var area = mwd.getElementById(element_id);
	 var parent_page =  mw.$('#mw-parent-page-value-<?php print $rand; ?>').val();
	 var content_id =  mw.$('#mw-content-id-value-<?php print $rand; ?>').val();
	 var content_type =  mw.$('#mw-content-type-value-<?php print $rand; ?>').val() 
	 var subtype =  mw.$('#mw-content-subtype-value-<?php print $rand; ?>').val();
	 
	 
	 
	 
	 
     var active_site_template =  $('#mw-active-template-value-<?php print $rand; ?>').val();
	 	  
 	 var active_site_layout = $('#mw-layout-file-value-<?php print $rand; ?>').val();
 
	 
	 if(area !== null){
		var params = {};
		params.content_id=content_id;
		params.content_type=content_type;
		params.subtype=subtype;
		params.parent_page=parent_page;
		params.inherit_template_from=parent_page;
		if(active_site_template != undefined && active_site_template != ''){
			params.preview_template=active_site_template
		}
		if(active_site_layout != undefined && active_site_layout != ''){
			params.preview_layout=active_site_layout
		}
		if(typeof window.mweditor !== 'undefined'){
			 $(mweditor).remove();
			 delete window.mweditor;
		}
		
		  mweditor = mw.admin.editor.init(area, params);
		
			 //	 mweditor = mw.admin.editor.OLDinit(area, params);

		/* new editor !!! */
		
		//mweditor = mw.tools.wysiwyg(area, params);
		//$(area).show();
		
		
		
		
	 }
	 var layout_selector =  mw.$('#mw-quick-add-choose-layout');
     if(layout_selector !== null){
       layout_selector.attr('inherit_from', parent_page);
       mw.reload_module('#mw-quick-add-choose-layout');
     }
}
mw.edit_content.before_save = function(){
	mw.askusertostay=false;
	if(window.parent != undefined && window.parent.mw != undefined){
		window.parent.mw.askusertostay=false;
	}
}
mw.edit_content.after_save = function(saved_id){
	mw.askusertostay=false;
	var content_id =  mw.$('#mw-content-id-value-<?php print $rand; ?>').val();
	var quick_add_holder = mwd.getElementById('mw-quick-content');
 	if(quick_add_holder != null){
 	    mw.tools.removeClass(quick_add_holder, 'loading');
	}
	if(content_id == 0){
			if(saved_id !== undefined){
 		        mw.$('#mw-content-id-value-<?php print $rand; ?>').val(saved_id);
 			}
			<?php if($is_quick!=false) : ?>
			 mw.$('#quickform-<?php print $rand; ?>').hide();
			 mw.$('#post-added-alert-<?php print $rand; ?>').show();
			<?php endif; ?>
  	}

	if(parent !== self && !!parent.mw){
		    mw.reload_module_parent('posts');
			mw.reload_module_parent('shop/products');
			mw.reload_module_parent('shop/cart_add');
			mw.reload_module_parent('pages');
			mw.reload_module_parent('content');
			mw.reload_module_parent('custom_fields');
		    mw.tools.removeClass(mwd.getElementById('mw-quick-content'), 'loading');
			mw.reload_module('pages');
    	    parent.mw.askusertostay = false;
    	<?php if($is_current!=false) :  ?>
    	if(window.parent.mw.history != undefined){
 			setTimeout(function(){
 				window.parent.mw.history.load('latest_content_edit');
			},200);
    	}
    	<?php endif; ?>
	} else {
		mw.reload_module('[data-type="pages"]', function(){
			if( mw.$("#pages_tree_toolbar .mw_del_tree_content").length === 0 ){
				mw.$("#pages_tree_toolbar").removeClass("activated");
				mw.treeRenderer.appendUI('#pages_tree_toolbar');
				mw.tools.tree.recall(mwd.querySelector('.mw_pages_posts_tree'));
			}
			mw.tools.removeClass(mwd.getElementById('mw-quick-content'), 'loading');
		 });
	}
}

mw.edit_content.set_category = function(id){
      /* FILLING UP THE HIDDEN FIELDS as you change category or parent page */

	  var names = [];
      var inputs = mwd.getElementById(id).querySelectorAll('input[type="checkbox"]'), i=0, l = inputs.length;
      for( ; i<l; i++){
        if(inputs[i].checked === true){
           names.push(inputs[i].value);
        }
      }
      if(names.length > 0){
        mw.$('#mw_cat_selected_for_post').val(names.join(',')).trigger("change");
      } else {
        mw.$('#mw_cat_selected_for_post').val('__EMPTY_CATEGORIES__').trigger("change");
      }
	  var names = [];
      var inputs = mwd.getElementById(id).querySelectorAll('input[type="radio"]'), i=0, l = inputs.length;
      for( ; i<l; i++){
        if(inputs[i].checked === true){
           names.push(inputs[i].value);
        }
      }
      if(names.length > 0){
        mw.$('#mw-parent-page-value-<?php print $rand; ?>').val(names[0]).trigger("change");
      } else {
        mw.$('#mw-parent-page-value-<?php print $rand; ?>').val(0).trigger("change");
      }
}

mw.edit_content.render_category_tree = function(id){
    if(mw.treeRenderer != undefined){
    	   mw.treeRenderer.appendUI('#mw-category-selector-'+id);
    	   mw.tools.tag({
    		  tagholder:'#mw-post-added-'+id,
    		  items: ".mw-ui-check",
    		  itemsWrapper: mwd.querySelector('#mw-category-selector-'+id),
    		  method:'parse',
    		  onTag:function(){
    			 mw.edit_content.set_category('mw-category-selector-'+id);
    		  },
    		  onUntag:function(a){
    			 mw.edit_content.set_category('mw-category-selector-'+id);
    		  }
    	  })
          $(mwd.querySelectorAll('#mw-category-selector-'+id+" .pages_tree_item")).bind("mouseup", function(e){
              if(!mw.tools.hasClass(e.target, 'mw_toggle_tree')){
                $(this).toggleClass("active");
              }
          });
    }
}

mw.edit_content.handle_form_submit = function(go_live){
        if(mw.edit_content.saving){ return false; }
        mw.edit_content.saving = true;
		var el = this;
		var go_live_edit = go_live || false;
		var el = mwd.getElementById('quickform-<?php print $rand; ?>');
		if(el === null){
		    return;
		}
		mw.edit_content.before_save();
        var module =  $(mw.tools.firstParentWithClass(el, 'module'));
        var data = mw.serializeFields(el);
        module.addClass('loading');
		
		
		
		
        mw.content.save(data, {
          onSuccess:function(a){
              mw.$('.mw-admin-go-live-now-btn').attr('content-id',this);
              if(mw.notification != undefined){
                mw.notification.success('Content saved!');
              }
              if(parent !== self && !!window.parent.mw){
                 window.parent.mw.askusertostay=false;
				 if(typeof(data.is_active) !== 'undefined' && typeof(data.id) !== 'undefined'){
					   if((data.id) != 0){ 
						  if((data.is_active) == 'n'){
							 window.parent.mw.$('.mw-set-content-unpublish').hide();
							 window.parent.mw.$('.mw-set-content-publish').show();
						  }
						  else if((data.is_active) == 'y'){
							  window.parent.mw.$('.mw-set-content-publish').hide();
							  window.parent.mw.$('.mw-set-content-unpublish').show();
						  }
					   }
					  
				 }
              }
			 
			  if(typeof(this) != "undefined"){
			var inner_edits = mw.collect_inner_edit_fields();
			
			if(inner_edits != undefined && inner_edits != false){
				var save_inner_edit_data = inner_edits;
				save_inner_edit_data.id = this;
				 
				mw.save_inner_editable_fields(save_inner_edit_data);
			}
		}
			  
			  
			  
			  
              if(go_live_edit != false){
    		    if(parent !== self && !!window.parent.mw){
    				 if(window.parent.mw.drag != undefined && window.parent.mw.drag.save != undefined){
    					 window.parent.mw.drag.save();
    				 }
                     window.parent.mw.askusertostay=false;
                }
                $.get('<?php print site_url('api_html/content_link/?id=') ?>'+this, function(data) {
                  window.top.location.href = data+'/editmode:y';
                });
              }
              else {
				  $.get('<?php print site_url('api_html/content_link/?id=') ?>'+this, function(data) {
					  if(data == null){
						return false;
					  }
					  var slug = data.replace("<?php print site_url() ?>", "").replace("/", "");
					  mw.$("#edit-content-url").val(slug);
					  mw.$(".view-post-slug").html(slug);
                   	  mw.$("a.quick-post-done-link").attr("href",data+'/editmode:y');
			 		  mw.$("a.quick-post-done-link").html(data);
                 });
                  mw.$("#<?php print $module_id ?>").attr("content-id",this);
                  <?php if($is_quick !=false) : ?>
                //  mw.$("#<?php print $module_id ?>").attr("just-saved",this);
                  <?php else: ?>
                  if(self === parent){
                    //var type =  el['subtype'];
                    mw.url.windowHashParam("action", "editpage:" + this);
                  }
                  <?php endif; ?>
                  mw.edit_content.after_save(this);
              }
              mw.edit_content.saving = false;
          },
          onError:function(){
              module.removeClass('loading');
              if(typeof this.title !== 'undefined'){
                mw.notification.error('Please enter title');
				$('.mw-title-field').animate({
				    paddingLeft: "+=5px",
 				    backgroundColor: "#efecec"
				})
                .animate({
    				paddingLeft: "-=5px",
     				backgroundColor: "white"
				});
              }
              if(typeof this.content !== 'undefined'){
                mw.notification.error('Please enter content');
              }
              if(typeof this.error !== 'undefined'){
                mw.session.checkPause = false;
                mw.session.checkPauseExplicitly = false;
                mw.session.logRequest();
              }
              mw.edit_content.saving = false;
          }
        });
}



mw.collect_inner_edit_fields = function(data) {
   
   
   
    var el = mwd.getElementById('quick_content_<?php print $rand ?>');
	
    if(el === null){
        return;
    }
	if(data != undefined){
		
	} else {
		var ifame_el = el.querySelector('iframe');
		if(ifame_el != null){
	     data = 	ifame_el.contentWindow.document.body.innerHTML
		}
	
 
	}
	
 	var doc = mw.tools.parseHtml(data);
 	var edits = $(doc).find('.edit.changed,.edit.orig_changed');
	 
    var master = {};
    if (edits.length > 0) {
        edits.each(function (j) {
            j++;
            var _el = $(this);
            if (($(this).attr("rel") == undefined || $(this).attr("rel") == '') && $(this).dataset('rel') == '') {
                mw.tools.foreachParents(this, function (loop) {
                    var cls = this.className;
                    if (mw.tools.hasClass(cls, 'edit') && mw.tools.hasClass(cls, 'changed') && (typeof this.attributes['rel'] !== 'undefined' || $(this).dataset('rel') != '')) {
                        _el = $(this);
                        mw.tools.stopLoop(loop);
                    }
                });
            }
            if ((typeof _el.attr("rel") != 'undefined' && _el.attr("rel") != '') || _el.dataset('rel') != '') {
                var content = _el.html();
                var attr_obj = {};
                var attrs = _el.get(0).attributes;
                if (attrs.length > 0) {
                    for (var i = 0; i < attrs.length; i++) {
                        temp1 = attrs[i].nodeName;
                        temp2 = attrs[i].nodeValue;
                        attr_obj[temp1] = temp2;
                    }
                }
                var obj = {
                    attributes: attr_obj,
                    html: content
                }
                var objX = "field_data_" + j;
                var arr1 = [{
                    "attributes": attr_obj
                }, {
                    "html": (content)
                }];
                master[objX] = obj;
            } else {}
        });
    }
	
	var  len = mw.tools.objLenght(master);
	 if(len > 0){
	    return master;
	 }
}

mw.save_inner_editable_fields = function(data){
 
	$.ajax({
    	type: 'POST',
    	url: mw.settings.site_url + 'api/save_edit',
    	data: data,
    	datatype: "json",
    	async: true,
    	beforeSend: function() {

        }
	});
	
}


/* END OF FUNCTIONS */

</script> 

<script>
    $(mwd).ready(function(){

        mw.edit_content.load_editor();
       <?php if($just_saved!=false) : ?>
       mw.$("#<?php print $module_id ?>").removeAttr("just-saved");
       <?php endif; ?>
       mw.edit_content.render_category_tree("<?php print $rand; ?>");
        mw.$("#quickform-<?php print $rand; ?>").submit(function(){
          mw.edit_content.handle_form_submit();
          return false;
        });
		<?php if($data['id']!=0) : ?>
		    mw.$(".mw-admin-go-live-now-btn").attr('content-id',<?php print $data['id']; ?>);
		<?php endif; ?>
		/* reloading the editor on parent change */
       mw.$('#mw-parent-page-value-<?php print $rand; ?>').bind('change', function(e){
		 var iframe_ed = $('.mw-iframe-editor')
	     var changed =  iframe_ed.contents().find('.changed').size();
		 if(changed == 0){
			  mw.edit_content.load_editor();
		 }
       });
	   
	   
	   
	    $(window).bind('templateChanged', function(e){
		 
		 var iframe_ed = $('.mw-iframe-editor')
	     var changed =  iframe_ed.contents().find('.changed').size();
		 if(changed == 0){
			   mw.edit_content.load_editor();
			 
		 }

		 
       });
	   
	   
	   
	 
	   
	   
	   
	   
	   
       mww.QTABS = mw.tools.tabGroup({
          nav: mw.$("#quick-add-post-options .mw-ui-btn"),
          tabs: mw.$(".quick-add-post-options-item"),
          toggle:true,
          onclick:function(){
            mw.tools.scrollTo(".quick-add-post-options-item:visible:last")
          }
       });
       if(mwd.querySelector("#quick-add-gallery-items .admin-thumb-item") !== null){
           QTABS.set(0);
       }


    });
</script>
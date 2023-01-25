{** departments section **}

{capture name="mainbox"}

    <form action="{""|fn_url}" method="post" id="departments_form" name="departments_form" enctype="multipart/form-data">
        <input type="hidden" name="fake" value="1" />
        {include "common/pagination.tpl" save_current_page=true save_current_url=true div_id="pagination_contents_departments"}

        {$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

        {$rev=$smarty.request.content_id|default:"pagination_contents_departments"}
        {include_ext "common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
        {include_ext "common/icon.tpl" class="icon-dummy" assign=c_dummy}
        {$department_statuses=""|fn_get_default_statuses:true}
        {$has_permission = fn_check_permissions("departments", "update_status", "admin", "POST")}
        {$image_width = $settings.Thumbnails.product_admin_mini_icon_width}
        {$image_height = $settings.Thumbnails.product_admin_mini_icon_height}

        {if $departments}
            {capture name="departments_table"}
                <div class="table-responsive-wrapper longtap-selection">
                    <table class="table table-middle table--relative table-responsive">
                        <thead data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
                            <tr>
                                <th width="6%" class="left mobile-hide">
                                    {include "common/check_items.tpl" is_check_disabled=!$has_permission check_statuses=($has_permission) ? $department_statuses : "" }

                                    <input type="checkbox" class="bulkedit-toggler hide"
                                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]" />
                                </th>
                                <th width="8%" class="table__column-without-title"></th>
                                <th><a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}"
                                        data-ca-target-id={$rev}>{__("name")}{if $search.sort_by === "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a>
                                </th>
                                <th width="20%"><a class="cm-ajax"
                                        href="{"`$c_url`&sort_by=timestamp&sort_order=`$search.sort_order_rev`"|fn_url}"
                                        data-ca-target-id={$rev}>{__("creation_date")}{if $search.sort_by === "timestamp"}{$c_icon nofilter}{/if}</a>
                                </th>

                                {hook name="departments:manage_header"}
                                {/hook}

                                <th width="6%" class="mobile-hide">&nbsp;</th>
                                <th width="10%" class="right"><a class="cm-ajax"
                                        href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}"
                                        data-ca-target-id={$rev}>{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</a>
                                </th>
                            </tr>
                        </thead>
                        {foreach $departments as $department}
                            <tr class="cm-row-status-{$department.status|lower} cm-longtap-target"
                                {if $has_permission}
                                    data-ca-longtap-action="setCheckBox"
                                    data-ca-longtap-target="input.cm-item"
                                    data-ca-id="{$department.department_id}"
                                {/if}
                            >

                                {$allow_save=$department|fn_allow_save_object:"departments"}

                                {if $allow_save}
                                    {$no_hide_input="cm-no-hide-input"}
                                {else}
                                    {$no_hide_input=""}
                                {/if}

                                <td width="6%" class="left mobile-hide">
                                    <input type="checkbox" name="department_ids[]" value="{$department.department_id}" class="cm-item
                                        {$no_hide_input} cm-item-status-{$department.status|lower} hide" /></td>

                                <td width="8%" class="departments-list__image left">
                                    {include "common/image.tpl"
                                        image=$department.main_pair.icon|default:$department.main_pair.detailed
                                        image_id=$departments.main_pair.image_id
                                        image_width=$image_width
                                        image_height=$image_height
                                        href="departments.update?department_id=`$department.department_id`"|fn_url
                                        image_css_class="departments-list__image--img"
                                        link_css_class="departments-list__image--link"}
                                </td>
                                <td data-th="{__("name")}">
                                    {$department.name}
                                </td>
                                <td width="20%" data-th="{__("creation_date")}">
                                    {$department.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                                </td>

                                {hook name="departments:manage_data"}
                                {/hook}

                                <td width="6%" class="mobile-hide">
                                    {capture name="tools_list"}
                                        <li>{btn type="list" text=__("edit") href="departments.update?department_id=`$department.department_id`"}
                                        </li>
                                        {if $allow_save}
                                            <li>{btn type="list" class="cm-confirm" text=__("delete") href="departments.delete?department_id=`$department.department_id`" method="POST"}
                                            </li>
                                        {/if}
                                    {/capture}
                                    <div class="hidden-tools">
                                        {dropdown content=$smarty.capture.tools_list}
                                    </div>
                                </td>
                                <td width="10%" class="right" data-th="{__("status")}">
                                    {include "common/select_popup.tpl" id=$department.department_id status=$department.status hidden=true
                                        object_id_name="department_id" table="departments" popup_additional_class="`$no_hide_input` dropleft"}
                                </td>
                            </tr>
                        {/foreach}
                    </table>
                </div>
            {/capture}

            {include "common/context_menu_wrapper.tpl"
                form="departments_form"
                object="departments"
                items=$smarty.capture.departments_table
                has_permissions=$has_permission
            }
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {include "common/pagination.tpl" div_id="pagination_contents_departments"}

        {capture name="adv_buttons"}
            {hook name="departments:adv_buttons"}
            {include "common/tools.tpl" tool_href="departments.add" prefix="top" hide_tools="true" title=__("add_department") icon="icon-plus"}
            {/hook}
        {/capture}

    </form>

{/capture}

{capture name="sidebar"}
    {hook name="departments:manage_sidebar"}
    {include "common/saved_search.tpl" dispatch="departments.manage" view_type="departments"}
    {include "addons/departments/views/departments/components/departments_search_form.tpl" dispatch="departments.manage"}
    {/hook}
{/capture}

{hook name="departments:manage_mainbox_params"}
{$page_title = __("departments")}
{$select_languages = true}
{/hook}

{include "common/mainbox.tpl" title=$page_title content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons select_languages=$select_languages sidebar=$smarty.capture.sidebar}

{** ad section **}


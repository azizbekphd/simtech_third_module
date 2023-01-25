{if $department}
    {$id=$department.department_id}
{else}
    {$id=0}
{/if}

{** departments section **}

{$allow_save = $department|fn_allow_save_object:"departments"}
{$hide_inputs = ""|fn_check_form_permissions}

{capture name="mainbox"}

    <form action="{""|fn_url}" method="post"
        class="form-horizontal form-edit{if !$allow_save || $hide_inputs} cm-hide-inputs{/if}" name="departments_form"
        enctype="multipart/form-data">
        <input type="hidden" class="cm-no-hide-input" name="fake" value="1" />
        <input type="hidden" class="cm-no-hide-input" name="department_id" value="{$id}" />

        <div id="content_general">
            {hook name="departments:general_content"}
            <div class="control-group">
                <label for="elm_department_name" class="control-label cm-required">{__("name")}</label>
                <div class="controls">
                    <input type="text" name="department_data[name]" id="elm_department_name" value="{$department.name}"
                        size="25" class="input-large" />
                </div>
            </div>

            <div class="control-group" id="department_graphic">
                <label class="control-label">{__("image")}</label>
                <div class="controls">
                    {include "common/attach_images.tpl"
                            image_name="departments_main"
                            image_object_type="department_logos"
                            image_pair=$department.main_pair
                            image_object_id=$id
                            no_detailed=true
                            hide_titles=true
                        }
                </div>
            </div>

            <div class="control-group" id="department_text">
                <label class="control-label" for="elm_department_description">{__("description")}:</label>
                <div class="controls">
                    <textarea id="elm_department_description" name="department_data[description]" cols="35" rows="8"
                        class="cm-wysiwyg input-large">{$department.description}</textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_department_supervisor">{__("departments.supervisor")}</label>
                <div class="controls readonly">
                    {include "pickers/users/picker.tpl"
                            data_id="elm_department_supervisor"
                            input_name="department_data[supervisor_id]"
                            item_ids=[$department.supervisor_id]
                            user_info=$department.supervisor_data
                            but_text=__("departments.select_supervisor")
                            display="radio"
                            view_mode="single_button"
                            extra_url="&for_departments=&exclude_user_types[]=C"}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_department_employees">{__("departments.employees")}</label>
                <div class="controls readonly">
                    {include "pickers/users/picker.tpl"
                            data_id="elm_department_employees"
                            input_name="department_data[employee_ids]"
                            item_ids=$department.employee_ids
                            but_text=__("departments.add_employees")
                            extra_url="&for_departments=&exclude_user_types[]=A&exclude_user_types[]=V"
                            no_item_text=__("departments.no_employees_defined")}
                </div>
            </div>

            {include "views/localizations/components/select.tpl" data_name="department_data[localization]" data_from=$department.localization}

            {include "common/select_status.tpl" input_name="department_data[status]" id="elm_department_status" obj_id=$id obj=$department hidden=true}
            {/hook}

            {if $id}
                <div class="control-group">
                    <label class="control-label" for="elm_department_timestamp_{$id}">{__("creation_date")}</label>
                    <div class="controls readonly">
                        <p>
                            {$department.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                        </p>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="elm_department_timestamp_{$id}">{__("departments.upd_date")}</label>
                    <div class="controls readonly">
                        <p>
                            {$department.upd_timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                        </p>
                    </div>
                </div>
            {/if}
            <!--content_general-->
        </div>

        <div id="content_addons" class="hidden clearfix">
            {hook name="departments:detailed_content"}
            {/hook}
            <!--content_addons-->
        </div>

        {capture name="buttons"}
            {if !$id}
                {include "buttons/save_cancel.tpl" but_role="submit-link" but_target_form="departments_form" but_name="dispatch[departments.update]"}
            {else}
                {if "ULTIMATE"|fn_allowed_for && !$allow_save}
                    {$hide_first_button=true}
                    {$hide_second_button=true}
                {/if}
                {include "buttons/save_cancel.tpl" but_name="dispatch[departments.update]" but_role="submit-link" but_target_form="departments_form" hide_first_button=$hide_first_button hide_second_button=$hide_second_button save=$id}
            {/if}
        {/capture}

    </form>

{/capture}

{include "common/mainbox.tpl"
    title=($id) ? $department.name : __("departments.new_department")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    select_languages=true}

{** department section **}


<div id="department_features_{$block.block_id}">
    <div class="ty-feature">
        {if $department.main_pair}
            <div class="ty-feature__image">
                {include "common/image.tpl"
                    images=$department.main_pair.icon|default:$department.main_pair.detailed
                    image_id=$departments.main_pair.image_id
                    image_width=300}
            </div>
        {/if}
        <div class="ty-feature__description ty-wysiwyg-content">
            {$department.description nofilter}
        </div>
    </div>

    {if $department.employees}
        <h3>{__("departments.employees")}:</h3>
        <li>
            {foreach $department.employees as $employee}
            <li>
                {$employee.firstname}
                {$employee.lastname}
                (<a href="mailto:{$employee.email}">{$employee.email}</a>)
            </li>
        {/foreach}
        </li>
    {else}
        <p class="ty-no-items">{__("departments.no_employees_defined")}</p>
    {/if}
    <!--department_features_{$block.block_id}-->
</div>
{capture name="mainbox_title"}{$department.name nofilter}{/capture}


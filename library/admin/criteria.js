jQuery(function ($) {

    let rowIndex = $('#criteria-rows tr').length;

    const fieldOptions = Object.entries(
        criteriaBuilderData.fields
    ).map(([id, label]) => {

        return `<option value="${id}">
                ${label}
            </option>`;
    }).join('');

    $('#add-criteria-row').on('click', function () {

        const row = `
        <tr>

            <td>

            <select name="job_criteria_rules[${rowIndex}][field]">

                <option value="">Select field</option>

                ${fieldOptions}

            </select>

            </td>

            <td>
                <select
                    name="job_criteria_rules[${rowIndex}][operator]"
                >
                    <option value=">">></option>
                    <option value="<"><</option>
                    <option value=">=">>=</option>
                    <option value="<="><=</option>
                    <option value="=">=</option>
                    <option value="!=">!=</option>
                    <option value="contains">contains</option>
                    <option value="between">between</option>
                    <option value="in">in</option>
                </select>
            </td>

            <td>

                <textarea
                name="job_criteria_rules[${rowIndex}][criteria]"
                rows="3"
                style="width:100%;"
                ></textarea>

            </td>

            <td>
                <button
                    type="button"
                    class="remove-row button"
                >
                    Remove
                </button>
            </td>

        </tr>
        `;

        $('#criteria-rows').append(row);

        rowIndex++;

    });

    $(document).on(
        'click',
        '.remove-row',
        function () {

            $(this)
                .closest('tr')
                .remove();

        }
    );

});
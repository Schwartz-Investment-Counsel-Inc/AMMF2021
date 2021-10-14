var show_id_in_value_for_added_inputs = false;

/**
 *
 */
function remove_row(row_id) {

    var row = document.getElementById(row_id);
   
    if (!row) {
        assertion_failed("Row doesn't exist with id: "+row_id+". Cannot remove row.");
        return false;
    }
   
    row.parentNode.removeChild(row);
   
    return false;
}

/**
 *
 */
function add_row(table_id, fund_type) {

    var table = document.getElementById(table_id);
   
    if (!table) {
        assertion_failed("Table doesn't exist with id: "+table_id+". Cannot add row to table.");
        return false;
    }
   
    var next_pk = get_next_available_pk(table);
   
    switch (table_id) {
        case 'fund_price':
            add_row_to_fund_price(next_pk);
            break;
         
        case 'total_returns':
            add_row_to_total_returns();
            break;
         
        case 'fund_information':
            add_row_to_fund_information(next_pk);
            break;
      
        default:
            assertion_failed("Unrecognized table id: "+table_id+". Cannot add row.");
            return false;
    }
   
    return false;
}

/**
 *
 */
function add_row_to_fund_price(pk) {
   
    var table_id = 'fund_price';
    var table = document.getElementById(table_id);
   
    if (!table) {
        assertion_failed("Could not find table in add_row_to_fund_price. Cannot add row to table.");
        return;
    }
   
    add_row_to_name_value_table(table, table_id, pk);
}

/**
 *
 */
function add_col_to_total_returns() {

    var table_id = 'total_returns';
    var table = document.getElementById(table_id);

    if (!table) {
        assertion_failed("Could not find table in add_col_to_total_returns. Cannot add col to table.");
        return;
    }

    var row_max = get_highest_row_id_from_dynamic_table(table);
    var col_max = get_highest_col_id_from_dynamic_table(table);
    var col_index = col_max + 1;

    // Step #1: Add the header field
    {
        var headerRowId = 'total_returns_headers';
        var headerRow = document.getElementById(headerRowId);

        if (!headerRow) {
            assertion_failed("Could not find tr#"+headerRowId+" in add_col_to_total_returns. Cannot add col to table.");
            return;
        }

        var headerCell = document.createElement('th');
        headerRow.appendChild(headerCell);

        var col_input = document.createElement('input');
        var col_input_id = 'total_returns-header-'+col_index;
        col_input.setAttribute('type', 'text');
        col_input.setAttribute('name', col_input_id);
        col_input.setAttribute('id', col_input_id);

        if (col_index > 0) {
            col_input.setAttribute("class", "short");
        }

        headerCell.appendChild(col_input);
    }

    // Step #2: Add the cell and remove link to tr#total_returns_col_action
    {
        var removeRowId = 'total_returns_col_actions';
        var removeRow = document.getElementById(removeRowId);

        if (!removeRow) {
            assertion_failed("Could not find tr#"+removeRowId+" in add_col_to_total_returns. Cannot add col to table.");
            return;
        }

        var actionCell = document.createElement('td');
        removeRow.appendChild(actionCell);

        var remove_link = document.createElement('a');
        remove_link.setAttribute('href', window.location.pathname+"?remove_col="+col_index+"&table=total_returns");
        remove_link.setAttribute('class', "abbr-large");
        remove_link.setAttribute('onclick', "return confirm('If you made any changes, save them before removing a row or column from a table, as changes will be lost.\\n\\nReally remove column?');");
        actionCell.appendChild(remove_link);

        var remove_link_text = document.createTextNode('remove');
        remove_link.appendChild(remove_link_text);
    }

    // Step #3: For each column, add cell and input
    {
        for (var row=0; row <= row_max; row++) {
            add_input_cell_to_dymanic_table(table_id, row, col_index);
        }
    }
}

/**
 * Adds a data cell (td) and input to a dynamic table with the following naming conventions:
 *
 *   - Table row (tr) id: [table_name]-[row_index]
 *   - Table cell (td) id: [table_name]-cell-[row_index]-[col_index]
 *   - Input id: [table_name]-value-[row_index]-[col_index]
 */
function add_input_cell_to_dymanic_table(table_name, row_index, col_index) {

    var nextRowId = table_name+"-"+row_index;
    var nextRow = document.getElementById(nextRowId);

    if (!nextRow) {
        assertion_failed("Could not find tr#"+nextRowId+" in add_input_cell_to_dymanic_table.");
        return;
    }

    var cell = document.createElement('td');
    nextRow.appendChild(cell);
    var cell_id = table_name+"-cell-"+row_index+"-"+col_index;
    cell.setAttribute("id", cell_id);

    var input = document.createElement('input');
    var input_id = table_name+'-value-'+row_index+"-"+col_index;
    input.setAttribute('type', 'text');
    input.setAttribute('name', input_id);
    input.setAttribute('id', input_id);

    if (col_index > 0) {
        input.setAttribute("class", "short");
    }

    cell.appendChild(input);
}

/**
 * The tables will likely change in the future. This one method parses the inputs and decides which method to call to save changes for various tables.
 */
function add_row_to_total_returns() {

    var table_id = 'total_returns';
    var table = document.getElementById(table_id);

    if (!table) {
        assertion_failed("Could not find table in add_row_to_total_returns. Cannot add row to table.");
        return;
    }

    var row_max = get_highest_row_id_from_dynamic_table(table);
    var col_max = get_highest_col_id_from_dynamic_table(table);

    var row_index = row_max + 1;

    var row_id = 'total_returns-'+row_index;

    var row = document.createElement('tr');
    row.setAttribute('name', row_id);
    row.setAttribute('id', row_id);
    table.appendChild(row);

    // Add td: actions
    {
        var actionCell = document.createElement('td');
        row.appendChild(actionCell);

        var remove_link = document.createElement('a');
        remove_link.setAttribute('href', window.location.pathname+"?remove_row="+row_index+"&table=total_returns");
        remove_link.setAttribute('class', "abbr-large");
        remove_link.setAttribute('onclick',"return confirm('If you made any changes, save them before removing a row or column from a table, as changes will be lost.\\n\\nReally remove row?');");
        actionCell.appendChild(remove_link);

        var remove_link_text = document.createTextNode('remove');
        remove_link.appendChild(remove_link_text);
    }

    for (var col=0; col <= col_max; col++) {
        add_input_cell_to_dymanic_table(table_id, row_index, col);
    }
}

/**
 *
 */
function add_row_to_fund_information(pk) {
   
    var table_id = 'fund_information';
    var table = document.getElementById(table_id);
   
    if (!table) {
        assertion_failed("Could not find table in add_row_to_fund_information. Cannot add row to table.");
        return;
    }
   
    add_row_to_name_value_table(table, table_id, pk);
}

/**
 * 
 */
function add_row_to_name_value_table(table, table_id, pk) {
   
    var row_id = table_id+'-'+pk;
   
    var row = document.createElement('tr');
    row.setAttribute('name', row_id);
    row.setAttribute('id', row_id);
    table.appendChild(row);
   
    // Add td: name
    {
        var cell = document.createElement('td');
        row.appendChild(cell);
      
        // Add input: name
        var input = document.createElement('input');
        var input_id = table_id+'-name-'+pk;
        input.setAttribute('type', 'text');
        input.setAttribute('name', input_id);
        input.setAttribute('id', input_id);
        if (show_id_in_value_for_added_inputs) {
            input.setAttribute('value', input_id);
        } else {
            input.setAttribute('value', '');
        }
        cell.appendChild(input);
    }
   
    // Add td: field
    {
        var cell = document.createElement('td');
        row.appendChild(cell);
      
        // Add input: field
        // Add input: name
        var input = document.createElement('input');
        var input_id = table_id+'-value-'+pk;
        input.setAttribute('type', 'text');
        input.setAttribute('name', input_id);
        input.setAttribute('id', input_id);
        if (show_id_in_value_for_added_inputs) {
            input.setAttribute('value', input_id);
        } else {
            input.setAttribute('value', '');
        }
        cell.appendChild(input);
    }
   
    // Add td: actions
    {
        var cell = document.createElement('td');
        row.appendChild(cell);
      
        var link = document.createElement('a');
        link.setAttribute('href', window.location.pathname);
        link.setAttribute('onclick',"return remove_row('"+row_id+"');");
        cell.appendChild(link);
      
        var link_text = document.createTextNode('remove');
        link.appendChild(link_text);
    }
} // add_row_to_name_value_table

/**
 *
 */
function get_highest_row_id_from_dynamic_table(table) {
    return _get_highest_id_from_dynamic_table(table, 'row');
}

/**
 *
 */
function get_highest_col_id_from_dynamic_table(table) {
    return _get_highest_id_from_dynamic_table(table, 'col');
}

/**
 *
 */
function _get_highest_id_from_dynamic_table(table, type) {

    var cells = table.getElementsByTagName('td');

    var highest_col = -1;

    for (var i=0; i<cells.length; i++) {

        var cell = cells[i];

        var cell_id = cell.id;

        if (cell_id == "") {
            continue;
        }

        var col_number = _get_index_from_dynamic_table_cell(cell_id, type);

        if (col_number > highest_col) {
            highest_col = col_number;
        }
    }

    return highest_col;
}

/**
 *
 */
function parse_row_index_from_dynamic_table_cell(cell_id) {
    return _get_index_from_dynamic_table_cell(cell_id, 'row');
}

/**
 *
 */
function parse_col_index_from_dynamic_table_cell(cell_id) {
    return _get_index_from_dynamic_table_cell(cell_id, 'col');
}

/**
 *
 */
function _get_index_from_dynamic_table_cell(cell_id, type) {
    var temp = new Array();
    temp = cell_id.split('-');

    var index = -1;

    if (type == 'row') {
        index = 2;
    } else if (type == 'col') {
        index = 3;
    } else {
        assertion_failed("Unrecognized type parameter to _get_index_from_dynamic_table_cell: "+type);
        return -1;
    }

    if (temp.length == 4) {
        return parseInt(temp[index]);
    }

    return -1;
}

/**
 *
 */
function get_row_number_from_dynamic_table_cell(cell_id) {
    var temp = new Array();
    temp = cell_id.split('-');

    if (temp.length == 4) {
        return parseInt(temp[2]);
    }

    return -1;
}

/**
 *
 */
function get_next_available_pk(table) {

    var rows = table.getElementsByTagName('tr');
   
    var high_pk = 0;
   
    for (var i=0; i<rows.length; i++) {
        var next_pk = parse_pk_from_row(rows[i].id);
      
        if (next_pk > high_pk) {
            high_pk = next_pk;
        }
    }
   
    return high_pk + 1;
}

/**
 *
 */
function parse_table_from_row(row_id) {
    var temp = new Array();
    temp = row_id.split('-');
    return temp[0];
}

/**
 *
 */
function parse_pk_from_row(row_id) {
    var temp = new Array();
    temp = row_id.split('-');
    return parseInt(temp[1]);
}

/**
 *
 */
function confirm_redirect(msg, new_location) {
    if (confirm(msg)) {
        window.location = new_location;
    }
   
    return false;
}

/**
 *
 */
function tracer(msg) {
    alert("DEBUG> "+msg);
}

/**
 *
 */
function assertion_failed(msg) {
    alert("Assertion failed: "+msg+". Please contact developer with this error message and information and what you were doing and when.");
}


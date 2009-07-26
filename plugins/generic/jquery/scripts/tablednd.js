/**
 * tablednd.js
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Setup a table for dragging and dropping.
 *
 * Depends on the jquery.tablednd library.
 *
 * $Id$
 */

/**
 * Setup a table for dragging and dropping rows.
 */
function setupTableDND(tableID, moveHandler) {
    $(tableID).tableDnD({
	    // add this class to cells to make them handles for dragging the row
	    dragHandle: "drag",

	    onDrop: function(table, row) {
		// find the row we dropped on
		var rows = table.tBodies[0].rows;
		var prevRowId = null;
		for (var i=0; i<rows.length; i++) {
		    if (rows[i].id) { // skip nondata rows
			if (rows[i].id == row.id)
			    break;
			else
			    prevRowId=rows[i].id;
		    }
		}
		// update the sequence in the database
		var req = makeAsyncRequest();

		// id's are "context-##", remove the "context-"
		var url = moveHandler + '?id=' + row.id.split(/-/)[1];
		if (prevRowId != null) url += '&prevId=' + prevRowId.split(/-/)[1];
		sendAsyncRequest(req, url, null, 'GET');
	    },

 	    onAllowDrop: function(dragRow, dropRow) {
		// allow dropping only onto other data rows with the same "context-"
		if (dropRow.className == "data")
		    return true;//dragRow[0].id.split(/-/)[0] == dropRow.id.split(/-/)[0];
		else
		    return false;
	    }
	});
}

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
		var nextRowId = null;
		for (var i=0; i<rows.length; i++) {
		    if (rows[i].id) { // skip nondata rows
			if (rows[i].id == row.id) {
			    nextRowId = rows[i+1].id.split(/-/)[1];
			    break;
			}
			else
			    prevRowId = rows[i].id.split(/-/)[1];
		    }
		}
		// update the sequence in the database
		var req = makeAsyncRequest();

		// id's are "context-##", remove the "context-"
		var url = moveHandler + '?id=' + row.id.split(/-/)[1];

		// The prevId sent to the moveHandler so it can
		// sequence our dropped item immediately after it.  If
		// we are dropping above the first visible item we
		// instead pass the id of the next item, so the
		// moveHandler can sequence this item after it.  This
		// deals with the special case where we are dragging
		// and dropping on a multi-page table where the first
		// item in the displayed view is not the very first
		// item in the entire sequence.
		if (prevRowId != null)
		    url += '&prevId=' + prevRowId;
		else
		    url += '&nextId=' + nextRowId;

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

function updateAndInsertTemplate(selector, replaces, callback, target = null, moveElement = false, hideElement = false){
	template = jQuery(selector);

	if (moveElement == true) {
		item = template;
	}
	else {
		item = template.clone();
    }

	if (!moveElement) {
		item.removeClass(selector.replace(".", "").replace("#", ""));
	}

	text = item.html();

	for (templateI = 0; templateI < replaces.length; templateI += 2){
		replace = replaces[templateI];
		if(replace.indexOf("[") == -1){
			replace = "[" + replace + "]";
		}
		text = text.split(replace).join(replaces[templateI + 1]);
	}

	item.html(text);

	if(callback){
		callback(item);
	}

	if (target !== undefined) {
		if (target != null) {
			template = target;
		}

		template.before(item);
		if (!hideElement) {
			item.fadeIn();
		}
	}

	return item;
}

function updateAndInsertTemplateRows(selectorOuter, selectorInner, itemsToInsert, replaces, innerItems, replacesForInnerItems, target, filter) {

	templateInner = jQuery(selectorInner);
	templateOuter = jQuery(selectorOuter);

	numberOfCols = templateOuter.find('.templateColumn').length;

	item = null;
	columns = null;
	columnNo = 0;

	items = [];

	itemsActive = 0;

	for (i = 0; i < itemsToInsert.length; i++) {
		itemToInsert = itemsToInsert[i];
		for (inner = 0; inner < itemToInsert[innerItems].length; inner++) {
			innerItem = itemToInsert[innerItems][inner];

			if (filter != null && !filter(innerItem, itemToInsert)) {
				continue;
			}
			itemsActive++;

			if (columnNo % numberOfCols == 0) {
				item = templateOuter.clone();
				sel = selectorOuter.replace(".", "").replace("#", "");
				item.removeClass(sel);
				item.addClass(sel + "Added");

				items.push(item);
				columns = item.find('.templateColumn');
				columnNo = 0;
			}

			replaceForItem = [];

			for (iRep = 0; iRep < replaces.length; iRep += 2) {
				replaceForItem.push(replaces[iRep]);
				replaceForItem.push(itemToInsert[replaces[iRep + 1]]);
			}

			for (iRep = 0; iRep < replacesForInnerItems.length; iRep += 2) {
				replaceForItem.push(replacesForInnerItems[iRep]);
				replaceForItem.push(innerItem[replacesForInnerItems[iRep + 1]]);
			}

			item = updateAndInsertTemplate(selectorInner, replaceForItem, null);

			jQuery(columns[columnNo]).empty().append(item);
			columnNo++;
		}
	}

	for (i = 0; i < items.length; i++) {
		(target ?? templateOuter).before(items[i]);
		items[i].fadeIn();
	}

	return itemsActive;
}

function waitAWhile(milliseconds, callback) {
	setTimeout(callback, milliseconds);
}
import path from 'path';

const uniqueKeys = new Set();

function extractRegexPlugin({regex, extraKeys, fileOutput}) {
	fileOutput =
		fileOutput || path.join('locale', 'uiTranslationKeysBackend.json');
	regex = regex || /[ ."]t\([\s\S]*?'([^']+)'/g;
	extraKeys = extraKeys || [];

	return {
		name: 'extract-keys',
		transform(code, id) {
			if (id.endsWith('.vue')) {
				const matches = [...code.matchAll(regex)];
				if (matches.length) {
					for (const match of matches) {
						uniqueKeys.add(match[1]);
					}
				}
			}
			return code;
		},
		buildEnd() {
			for (const key of extraKeys) {
				uniqueKeys.add[key];
			}

			if (uniqueKeys.size) {
				const fs = require('fs');
				fs.writeFileSync(fileOutput, JSON.stringify([...uniqueKeys], null, 2));
				console.log(`Written all existing locale keys to ${fileOutput}`);
			}
		},
	};
}

module.exports = extractRegexPlugin;

(function () {
	var config;
	config = {
		name: "WoltLabSuite/_Meta",
		out: "WoltLabSuite.Core.Conversation.min.js",
		useStrict: true,
		preserveLicenseComments: false,
		optimize: 'none',
		excludeShallow: [
			'WoltLabSuite/_Meta'
		],
		rawText: {
			'WoltLabSuite/_Meta': 'define([], function() {});'
		},
		onBuildRead: function(moduleName, path, contents) {
			if (!process.versions.node) {
				throw new Error('You need to run node.js');
			}
			
			if (moduleName === 'WoltLabSuite/_Meta') {
				if (global.allModules === undefined) {
					var fs   = module.require('fs'),
					    path = module.require('path');
					global.allModules = [];
					
					var queue = ['WoltLabSuite'];
					var folder;
					while (folder = queue.shift()) {
						var files = fs.readdirSync(folder);
						for (var i = 0; i < files.length; i++) {
							var filename = path.join(folder, files[i]).replace(/\\/g, '/');
							if (filename === 'WoltLabSuite/Core/Acp') continue;
							
							if (path.extname(filename) === '.js') {
								global.allModules.push(filename);
							}
							else if (fs.statSync(filename).isDirectory()) {
								queue.push(filename);
							}
						}
					}
				}
				
				return 'define([' + global.allModules.map(function (item) { return "'" + item.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\.js$/, '') + "'"; }).join(', ') + '], function() { });';
			}
			
			return contents;
		}
	};
	
	var _isSupportedBuildUrl = require._isSupportedBuildUrl;
	require._isSupportedBuildUrl = function (url) {
		var result = _isSupportedBuildUrl(url);
		if (!result) return result;
		if (Object.keys(config.rawText).map(function (item) { return (process.cwd() + '/' + item + '.js').replace(/\\/g, '/'); }).indexOf(url.replace(/\\/g, '/')) !== -1) return result;

		var fs = module.require('fs');
		try {
			fs.statSync(url);
		}
		catch (e) {
			console.log('Unable to find module:', url, 'ignoring.');

			return false;
		}
		return true;
	};
	
	if (module) module.exports = config;

	return config;
})();

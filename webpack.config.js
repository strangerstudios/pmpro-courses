const path = require('path');
const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
	entry: {
		'./blocks/all-courses/block': './blocks/all-courses/block.js', // Set entry points to same as output points.
   './blocks/my-courses/block': './blocks/my-courses/block.js'
	},
	output: {
		path: path.resolve( __dirname ),
		filename: '[name].build.js', // Reference [name].build.js whenever enqueueing.
	}
}
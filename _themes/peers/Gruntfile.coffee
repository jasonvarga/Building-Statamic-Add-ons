module.exports = ->

	@initConfig
		sass:
			dist:
				options:
					style: 'compressed'
				files:
					'css/peers.css': 'sass/peers.sass'
		cssmin:
			options:
				keepSpecialComments: 0
			combine:
				files:
					'css/peers.css': ['bower_components/normalize-css/normalize.css', 'css/peers.css']
		watch:
			options:
				livereload: true
			css:
				files: ['sass/*.sass']
				tasks: ['sass', 'cssmin']

	@loadNpmTasks 'grunt-contrib-sass'
	@loadNpmTasks 'grunt-contrib-cssmin'
	@loadNpmTasks 'grunt-contrib-watch'
	@registerTask 'default', ['sass', 'cssmin']
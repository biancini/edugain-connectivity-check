// Karma configuration
// Generated on Sat Aug 29 2015 06:32:04 GMT+0000 (UTC)

module.exports = function(config) {
  config.set({

    // base path that will be used to resolve all patterns (eg. files, exclude)
    basePath: '../../',


    // frameworks to use
    // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
    frameworks: [
      'jasmine',
      'chai-as-promised',
      'chai'
    ],


    // list of files / patterns to load in the browser
    files: [
      'web/js/angular.min.js',
      'tests/view/angular-mocks.js',
      'web/js/eccs.js',
      'web/js/eccs-*.js',
      'tests/view/*.tests.js'
    ],


    // list of files to exclude
    exclude: [
    ],


    // preprocess matching files before serving them to the browser
    // available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
    preprocessors: {
      'web/js/eccs.js': 'coverage',
      'web/js/eccs-*.js': 'coverage'
    },


    // test results reporter to use
    // possible values: 'dots', 'progress'
    // available reporters: https://npmjs.org/browse/keyword/karma-reporter
    reporters: ['progress', 'junit', 'coverage'],


    // web server port
    port: 9876,


    // enable / disable colors in the output (reporters and logs)
    colors: true,


    // level of logging
    // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
    logLevel: config.LOG_INFO,


    // enable / disable watching file and executing tests whenever any file changes
    autoWatch: true,


    // start these browsers
    // available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
    browsers: ['Chrome'],


    // Continuous Integration mode
    // if true, Karma captures browsers, runs the tests and exits
    singleRun: false,

    junitReporter: {
      outputDir: 'tests/view/reports', // results will be saved as $outputDir/$browserName.xml
      outputFile: undefined, // if included, results will be saved as $outputDir/$browserName/$outputFile
      suite: '' // suite will become the package name attribute in xml testsuite element
    },

    coverageReporter: {
      type: 'lcov',
      dir: 'tests/view/reports',
      subdir: '.'
    },

    preprocessors: {
        'web/js/eccs.js': 'coverage',
        'web/js/eccs-*.js': 'coverage'
    },

    plugins: [
        'karma-jasmine',
        'karma-chai',
        'karma-chai-as-promised',
        'karma-phantomjs-launcher',
        'karma-coverage',
        'karma-junit-reporter',
        'karma-chrome-launcher'
    ]
  })
}

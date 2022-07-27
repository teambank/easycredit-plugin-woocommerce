module.exports = function(grunt) {

    var baseDir = 'src/woocommerce-gateway-ratenkaufbyeasycredit/assets';

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');

    grunt.initConfig({
      uglify: {
        easycredit: {
          options: {
            sourceMap: true,
            sourceMapName: baseDir+'/js/easycredit.min.js.map'
          },
          files: {
            [baseDir+'/js/easycredit.min.js']: [
                baseDir+'/js/src/easycredit-frontend.js'
            ],
            [baseDir+'/js/easycredit-backend.min.js']: [
                baseDir+'/js/easycredit-backend.js'
            ]
          },
        },
      },
      cssmin: {
          options: {
            mergeIntoShorthands: false,
            roundingPrecision: -1
          },
          easycredit: {
            files: {
              [baseDir+'/css/easycredit.min.css']: [
                baseDir+'/css/src/easycredit-frontend.css'
              ],
              [baseDir+'/css/easycredit-backend.min.css'] : [
                baseDir+'/css/src/easycredit-backend.css'
              ]
            }
          }
      },
    });
    grunt.registerTask('default', ['uglify','cssmin']);
}

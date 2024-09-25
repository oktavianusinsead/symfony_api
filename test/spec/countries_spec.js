import frisby from 'frisby';
import faker from 'faker';
import globals from '../globals';
import helpers from '../helpers';

helpers.testCORS(`${globals.host}/countries`, 'CORS log in endpoint' );

jasmine.getEnv().defaultTimeoutInterval = 30000;

describe( 'Countries Endpoint', function() {

    var studentAccessToken  = null,
        adminAccessToken    = null;

    beforeEach( done => {

        globals.credentials.username = globals.credentials.studentScope.username;
        globals.credentials.password = globals.credentials.studentScope.password;
        helpers.authenticate( 'mimstudent' ).then( token => {
            studentAccessToken = token;

            globals.credentials.username = globals.credentials.adminScope.username;
            globals.credentials.password = globals.credentials.adminScope.password;
            helpers.authenticate( 'mimadmin' ).then( token => {
                adminAccessToken = token;
                done();
})
})
})
    it ( 'Check mimstudent scope ' , function() {

        frisby.create( 'Search countries: Positive Test Case' )
            .get( `${globals.host}/countries` )
            .addHeader( 'Authorization', `Bearer ${studentAccessToken}` )
            .expectStatus( 200 )
            .expectJSONTypes( {
                countries: Array
            } )
            .toss();

    });

    it ( 'Check mimadmin scope ' , function() {

        frisby.create( 'Search countries: Positive Test Case' )
            .get( `${globals.host}/countries` )
            .addHeader( 'Authorization', `Bearer ${adminAccessToken}` )
            .expectStatus( 200 )
            .expectJSONTypes( {
                countries: Array
            } )
            .toss();

    });

});

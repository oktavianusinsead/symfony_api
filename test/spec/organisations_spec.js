import frisby from 'frisby';
import faker from 'faker';
import globals from '../globals';
import helpers from '../helpers';

helpers.testCORS(`${globals.host}/organisations`, 'CORS log in endpoint' );

jasmine.getEnv().defaultTimeoutInterval = 30000;

describe( 'Organisations Endpoint', function() {

    var studentAccessToken  = null,
        adminAccessToken    = null,
        validSearchString   = 'INS',
        invalidSearchString = '';

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

        frisby.create( 'Search organisationss: Positive Test Case' )
            .get( `${globals.host}/organisations?q=${validSearchString}` )
            .addHeader( 'Authorization', `Bearer ${studentAccessToken}` )
            .expectStatus( 200 )
            .expectJSONTypes( {
                organisations: Array
            } )
            .toss();

        frisby.create( 'Search organisations: Negative Test Case' )
            .get( `${globals.host}/organisations?q=${invalidSearchString}` )
            .addHeader( 'Authorization', `Bearer ${studentAccessToken}` )
            .expectStatus( 422 )
            .toss();

        frisby.create( 'Search job titles: Missing Parameter' )
            .get( `${globals.host}/organisations` )
            .addHeader( 'Authorization', `Bearer ${studentAccessToken}` )
            .expectStatus( 422 )
            .toss();

    });

    it ( 'Check mimadmin scope ' , function() {

        frisby.create( 'Search organisations: Positive Test Case' )
            .get( `${globals.host}/organisations?q=${validSearchString}` )
            .addHeader( 'Authorization', `Bearer ${adminAccessToken}` )
            .expectStatus( 200 )
            .expectJSONTypes( {
                organisations: Array
            } )
            .toss();

        frisby.create( 'Search organisations: Negative Test Case' )
            .get( `${globals.host}/organisations?q=${invalidSearchString}` )
            .addHeader( 'Authorization', `Bearer ${adminAccessToken}` )
            .expectStatus( 422 )
            .toss();

        frisby.create( 'Search job titles: Missing Parameter' )
                .get( `${globals.host}/organisations` )
                .addHeader( 'Authorization', `Bearer ${adminAccessToken}` )
                .expectStatus( 422 )
                .toss();

    });

});

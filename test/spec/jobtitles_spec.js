import frisby from 'frisby';
import faker from 'faker';
import globals from '../globals';
import helpers from '../helpers';

helpers.testCORS(`${globals.host}/job-titles`, 'CORS log in endpoint' );

jasmine.getEnv().defaultTimeoutInterval = 30000;

describe( 'Job Title Endpoint', function() {

    var studentAccessToken  = null,
        adminAccessToken    = null,
        validSearchString   = 'Tech',
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

        frisby.create( 'Search job titles: Positive Test Case' )
            .get( `${globals.host}/job-titles?q=${validSearchString}` )
            .addHeader( 'Authorization', `Bearer ${studentAccessToken}` )
            .expectStatus( 200 )
            .expectJSONTypes( {
                job_titles: Array
            } )
            .toss();

        frisby.create( 'Search job titles: Negative Test Case' )
            .get( `${globals.host}/job-titles?q=${invalidSearchString}` )
            .addHeader( 'Authorization', `Bearer ${studentAccessToken}` )
            .expectStatus( 422 )
            .toss();

        frisby.create( 'Search job titles: Missing Parameter' )
            .get( `${globals.host}/job-titles` )
            .addHeader( 'Authorization', `Bearer ${studentAccessToken}` )
            .expectStatus( 422 )
            .toss();

    });

    it ( 'Check mimadmin scope ' , function() {

        frisby.create( 'Search job titles: Positive Test Case' )
            .get( `${globals.host}/job-titles?q=${validSearchString}` )
            .addHeader( 'Authorization', `Bearer ${adminAccessToken}` )
            .expectStatus( 200 )
            .expectJSONTypes( {
                job_titles: Array
            } )
            .toss();

        frisby.create( 'Search job titles: Negative Test Case' )
            .get( `${globals.host}/job-titles?q=${invalidSearchString}` )
            .addHeader( 'Authorization', `Bearer ${adminAccessToken}` )
            .expectStatus( 422 )
            .toss();

        frisby.create( 'Search job titles: Missing Parameter' )
            .get( `${globals.host}/job-titles` )
            .addHeader( 'Authorization', `Bearer ${adminAccessToken}` )
            .expectStatus( 422 )
            .toss();
    });


});

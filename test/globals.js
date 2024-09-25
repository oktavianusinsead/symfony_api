export default {
  host: 'http://localhost:8000/api/v1.1',
  credentials:
  [
    {
      username: 'zoe.mckay@esuite.edutest',
      password: 'esuite2012',
      peoplesoftId: '0802149',
      type: 'coordinator'
    },
    {
      username: 'omar.abdo@esuite.edutest',
      password: 'esuite.2015',
      peoplesoftId: '0857286',
      type: 'student'
    },
    {
      username: 'usha.shenoy@esuite.edutest',
      password: 'esuite2012',
      peoplesoftId: '0642136',
      type: 'coordinator'
    },
    {
      username: 'victor.abramov@esuite.edutest',
      password: 'esuite.2015',
      peoplesoftId: '0857576',
      type: 'student'
    }
  ],
  CORS:
  {
    'Access-Control-Allow-Credentials': 'false',
    'Access-Control-Allow-Headers': 'Authorization, Content-Type, User-Agent, Cache-Control',
    'Access-Control-Allow-Methods': 'POST, PUT, GET, DELETE, OPTIONS',
    'Access-Control-Allow-Origin': '*'
  }
};

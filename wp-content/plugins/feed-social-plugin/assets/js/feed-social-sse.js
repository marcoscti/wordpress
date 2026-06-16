
const feedEvents = new EventSource('/feed-social-sse');
feedEvents.addEventListener('new-content-feed', function(event){
    console.log(JSON.parse(event.data));
});

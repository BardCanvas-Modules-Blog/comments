
function publish_comment(id_comment, callback)
{
    change_comment_status(id_comment, 'published', callback)
}

function reject_comment(id_comment, callback)
{
    change_comment_status(id_comment, 'rejected', callback)
}

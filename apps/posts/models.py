"""
Models for Posts (Text, Image, Video, Polls, Scheduled, Drafts, Comments, Reactions, Bookmarks).
"""
from django.db import models
from django.conf import settings

class Post(models.Model):
    """
    Core post model supporting text, carousels, media, polls, audience restriction,
    scheduled publishing, and drafts.
    """
    POST_TYPE_CHOICES = (
        ('standard', 'Standard Post'),
        ('carousel', 'Carousel Post'),
        ('poll', 'Poll Post'),
        ('collaborative', 'Collaborative Post'),
    )
    AUDIENCE_CHOICES = (
        ('public', 'Public'),
        ('followers', 'Followers Only'),
        ('close_friends', 'Close Friends Only'),
    )

    author = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='posts')
    content = models.TextField(blank=True)
    post_type = models.CharField(max_length=20, choices=POST_TYPE_CHOICES, default='standard')
    audience = models.CharField(max_length=20, choices=AUDIENCE_CHOICES, default='public')

    # Scheduling & Drafts
    is_draft = models.BooleanField(default=False)
    scheduled_for = models.DateTimeField(null=True, blank=True)
    published_at = models.DateTimeField(auto_now_add=True)

    # Metadata & Tags
    hashtags = models.JSONField(default=list, blank=True)
    mentions = models.JSONField(default=list, blank=True)
    allow_comments = models.BooleanField(default=True)

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Post {self.id} by {self.author.username}"


class PostMedia(models.Model):
    """
    Media attachments (images/videos) associated with a post.
    """
    post = models.ForeignKey(Post, on_delete=models.CASCADE, related_name='media')
    file = models.FileField(upload_to='posts_media/')
    media_type = models.CharField(max_length=10, choices=(('image', 'Image'), ('video', 'Video')))
    order = models.PositiveIntegerField(default=0)


class PollOption(models.Model):
    """
    Poll option model for poll posts.
    """
    post = models.ForeignKey(Post, on_delete=models.CASCADE, related_name='poll_options')
    option_text = models.CharField(max_length=200)
    votes = models.ManyToManyField(settings.AUTH_USER_MODEL, blank=True, related_name='poll_votes')


class Comment(models.Model):
    """
    Comments and nested replies on posts.
    """
    post = models.ForeignKey(Post, on_delete=models.CASCADE, related_name='comments')
    author = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    parent = models.ForeignKey('self', on_delete=models.CASCADE, null=True, blank=True, related_name='replies')
    content = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)


class LikeReaction(models.Model):
    """
    Likes and custom emoji reactions on posts.
    """
    post = models.ForeignKey(Post, on_delete=models.CASCADE, related_name='reactions')
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    reaction_type = models.CharField(max_length=20, default='like')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('post', 'user')


class Bookmark(models.Model):
    """
    Saved post collections for user bookmarks.
    """
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='bookmarks')
    post = models.ForeignKey(Post, on_delete=models.CASCADE)
    collection_name = models.CharField(max_length=100, default='All Posts')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('user', 'post')

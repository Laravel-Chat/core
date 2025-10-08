# Real-world Examples

Practical examples for common chat implementation scenarios.

## Table of Contents

- [Complete Chat Application](#complete-chat-application)
- [API Endpoints](#api-endpoints)
- [Frontend Integration](#frontend-integration)
- [Customer Support Chat](#customer-support-chat)
- [Team Collaboration](#team-collaboration)
- [Social Media Messaging](#social-media-messaging)
- [Real-time Notifications](#real-time-notifications)

---

## Complete Chat Application

### Backend: Laravel API

**Routes:**

```php
// routes/api.php
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;

Route::middleware('auth:sanctum')->group(function () {
    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);
    
    // Messages
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/conversations/{conversation}/mark-read', [MessageController::class, 'markRead']);
    
    // Search
    Route::get('/search/users', [ChatController::class, 'searchUsers']);
});
```

**Controllers:**

```php
namespace App\Http\Controllers;

use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;
use Illuminate\Http\Request;
use App\Models\User;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::getUserConversations($request->user());
        
        return response()->json($conversations);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:direct,group',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'title' => 'required_if:type,group|string|max:255',
        ]);
        
        $result = Conversation::create(
            $request->user(),
            $validated['type'],
            $validated['participant_ids'],
            $validated['title'] ?? null
        );
        
        return response()->json($result, 201);
    }
    
    public function show(Request $request, int $conversation)
    {
        if (!Conversation::validateParticipant($conversation, $request->user())) {
            abort(403, 'Not authorized');
        }
        
        $messages = Message::getConversationMessages($request->user(), $conversation);
        
        return response()->json($messages);
    }
    
    public function destroy(Request $request, int $conversation)
    {
        Conversation::delete($conversation, $request->user());
        
        return response()->json(['message' => 'Conversation deleted']);
    }
}

class MessageController extends Controller
{
    public function index(Request $request, int $conversation)
    {
        if (!Conversation::validateParticipant($conversation, $request->user())) {
            abort(403);
        }
        
        $messages = Message::getConversationMessages($request->user(), $conversation);
        
        return response()->json($messages);
    }
    
    public function store(Request $request, int $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'type' => 'nullable|in:text,image,file,audio,video',
            'metadata' => 'nullable|array',
        ]);
        
        $message = Message::send(
            $request->user(),
            $conversation,
            $validated['content'],
            $validated['type'] ?? 'text',
            $validated['metadata'] ?? null
        );
        
        return response()->json($message, 201);
    }
    
    public function destroy(Request $request, int $message)
    {
        Message::delete($message, $request->user());
        
        return response()->json(['message' => 'Message deleted']);
    }
    
    public function markRead(Request $request, int $conversation)
    {
        Message::markAsRead($request->user(), $conversation);
        
        return response()->json(['message' => 'Messages marked as read']);
    }
}

class ChatController extends Controller
{
    public function searchUsers(Request $request)
    {
        $query = $request->get('q');
        
        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'avatar']);
        
        return response()->json($users);
    }
}
```

---

### Frontend: Vue.js Component

```vue
<template>
  <div class="chat-app">
    <!-- Sidebar: Conversations List -->
    <div class="chat-sidebar">
      <div class="sidebar-header">
        <h2>Messages</h2>
        <button @click="showNewChatModal = true">New Chat</button>
      </div>
      
      <div class="conversations-list">
        <div
          v-for="conversation in conversations"
          :key="conversation.id"
          :class="['conversation-item', { active: activeConversation?.id === conversation.id }]"
          @click="selectConversation(conversation)"
        >
          <img :src="conversation.avatar_url" :alt="conversation.title" />
          <div class="conversation-info">
            <h3>{{ conversation.title }}</h3>
            <p class="last-message">{{ conversation.last_message?.content }}</p>
          </div>
          <span v-if="conversation.unread_count > 0" class="unread-badge">
            {{ conversation.unread_count }}
          </span>
        </div>
      </div>
    </div>
    
    <!-- Main: Chat Window -->
    <div class="chat-main">
      <div v-if="activeConversation" class="chat-window">
        <!-- Header -->
        <div class="chat-header">
          <h2>{{ activeConversation.title }}</h2>
          <button @click="deleteConversation">Delete</button>
        </div>
        
        <!-- Messages -->
        <div ref="messagesContainer" class="messages-container">
          <div
            v-for="message in messages"
            :key="message.id"
            :class="['message', { own: message.user_id === currentUser.id }]"
          >
            <div class="message-content">
              <p>{{ message.content }}</p>
              <span class="message-time">{{ formatTime(message.created_at) }}</span>
            </div>
          </div>
        </div>
        
        <!-- Input -->
        <div class="chat-input">
          <input
            v-model="newMessage"
            type="text"
            placeholder="Type a message..."
            @keyup.enter="sendMessage"
          />
          <button @click="sendMessage">Send</button>
        </div>
      </div>
      
      <div v-else class="no-conversation">
        <p>Select a conversation to start chatting</p>
      </div>
    </div>
    
    <!-- New Chat Modal -->
    <div v-if="showNewChatModal" class="modal">
      <div class="modal-content">
        <h2>New Conversation</h2>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search users..."
          @input="searchUsers"
        />
        <div class="user-list">
          <div
            v-for="user in searchResults"
            :key="user.id"
            class="user-item"
            @click="startConversation(user)"
          >
            <img :src="user.avatar" :alt="user.name" />
            <span>{{ user.name }}</span>
          </div>
        </div>
        <button @click="showNewChatModal = false">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { useListen } from '@laravel/echo-hooks';
import axios from 'axios';
import { format } from 'date-fns';

const currentUser = ref(null);
const conversations = ref([]);
const activeConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const showNewChatModal = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const messagesContainer = ref(null);

// Load current user
onMounted(async () => {
  const { data } = await axios.get('/api/user');
  currentUser.value = data;
  loadConversations();
});

// Load conversations
const loadConversations = async () => {
  const { data } = await axios.get('/api/conversations');
  conversations.value = data.conversations;
};

// Select conversation
const selectConversation = async (conversation) => {
  activeConversation.value = conversation;
  await loadMessages(conversation.id);
  await markAsRead(conversation.id);
};

// Load messages
const loadMessages = async (conversationId) => {
  const { data } = await axios.get(`/api/conversations/${conversationId}/messages`);
  messages.value = data.messages;
  await nextTick(() => scrollToBottom());
};

// Listen for new messages (Echo Hooks)
const { data: newMessageData } = useListen('MessageSent', {
  channel: activeConversation.value 
    ? `chat.conversation.${activeConversation.value.id}` 
    : null,
  channelType: 'private',
});

watch(newMessageData, (message) => {
  if (message && activeConversation.value?.id === message.conversation_id) {
    messages.value.push(message);
    scrollToBottom();
    markAsRead(message.conversation_id);
  }
});

// Send message
const sendMessage = async () => {
  if (!newMessage.value.trim() || !activeConversation.value) return;

  try {
    await axios.post(`/api/conversations/${activeConversation.value.id}/messages`, {
      content: newMessage.value,
      type: 'text',
    });
    
    newMessage.value = '';
  } catch (error) {
    console.error('Failed to send message:', error);
  }
};

// Mark as read
const markAsRead = async (conversationId) => {
  await axios.post(`/api/conversations/${conversationId}/mark-read`);
  
  // Update local conversation unread count
  const conv = conversations.value.find(c => c.id === conversationId);
  if (conv) {
    conv.unread_count = 0;
  }
};

// Delete conversation
const deleteConversation = async () => {
  if (!confirm('Delete this conversation?')) return;

  try {
    await axios.delete(`/api/conversations/${activeConversation.value.id}`);
    conversations.value = conversations.value.filter(
      c => c.id !== activeConversation.value.id
    );
    activeConversation.value = null;
    messages.value = [];
  } catch (error) {
    console.error('Failed to delete conversation:', error);
  }
};

// Search users
let searchTimeout;
const searchUsers = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    if (searchQuery.value.length < 2) {
      searchResults.value = [];
      return;
    }

    const { data } = await axios.get('/api/search/users', {
      params: { q: searchQuery.value }
    });
    searchResults.value = data;
  }, 300);
};

// Start new conversation
const startConversation = async (user) => {
  try {
    const { data } = await axios.post('/api/conversations', {
      type: 'direct',
      participant_ids: [user.id],
    });
    
    showNewChatModal.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    
    await loadConversations();
    
    const newConv = conversations.value.find(c => c.id === data.id);
    if (newConv) {
      selectConversation(newConv);
    }
  } catch (error) {
    console.error('Failed to create conversation:', error);
  }
};

// Utilities
const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const formatTime = (timestamp) => {
  return format(new Date(timestamp), 'HH:mm');
};
</script>

<style scoped>
.chat-app {
  display: flex;
  height: 100vh;
}

.chat-sidebar {
  width: 300px;
  border-right: 1px solid #ddd;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 1rem;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  display: flex;
  align-items: center;
  padding: 1rem;
  cursor: pointer;
  border-bottom: 1px solid #eee;
}

.conversation-item:hover,
.conversation-item.active {
  background: #f5f5f5;
}

.conversation-item img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 1rem;
}

.conversation-info {
  flex: 1;
}

.conversation-info h3 {
  margin: 0;
  font-size: 1rem;
}

.last-message {
  margin: 0.25rem 0 0;
  color: #666;
  font-size: 0.875rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-badge {
  background: #007bff;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.chat-window {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chat-header {
  padding: 1rem;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.message {
  margin-bottom: 1rem;
  display: flex;
}

.message.own {
  justify-content: flex-end;
}

.message-content {
  max-width: 60%;
  padding: 0.75rem;
  border-radius: 8px;
  background: #f0f0f0;
}

.message.own .message-content {
  background: #007bff;
  color: white;
}

.message-time {
  font-size: 0.75rem;
  opacity: 0.7;
  margin-top: 0.25rem;
  display: block;
}

.chat-input {
  padding: 1rem;
  border-top: 1px solid #ddd;
  display: flex;
  gap: 0.5rem;
}

.chat-input input {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.no-conversation {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  min-width: 400px;
}

.user-list {
  max-height: 300px;
  overflow-y: auto;
  margin: 1rem 0;
}

.user-item {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  cursor: pointer;
  border-radius: 4px;
}

.user-item:hover {
  background: #f5f5f5;
}

.user-item img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  margin-right: 1rem;
}
</style>
```

---

## Customer Support Chat

Implementation for customer support with agent assignment:

```php
// Custom Policy for Support
class SupportMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Customers can message support agents
        // Support agents can message anyone
        return $recipient->hasRole('support') || $sender->hasRole('support');
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        return true;
    }
}

// Support Controller
class SupportChatController extends Controller
{
    public function createSupportTicket(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Find available support agent
        $agent = User::where('role', 'support')
            ->where('is_available', true)
            ->inRandomOrder()
            ->first();

        if (!$agent) {
            return response()->json(['error' => 'No agents available'], 503);
        }

        // Create conversation
        $result = Conversation::create(
            $request->user(),
            'direct',
            [$agent->id]
        );

        // Send initial message
        Message::send(
            $request->user(),
            $result->id,
            $validated['message']
        );

        return response()->json([
            'conversation_id' => $result->id,
            'agent' => $agent,
        ]);
    }
}
```

---

## Navigation

← [Previous: api-reference.md](api-reference.md) | [Index](INDEX.md) 

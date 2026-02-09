<template>
    <v-app class="bg-grey-lighten-4">
        <!-- App Bar (Unified) -->
        <v-app-bar color="primary" elevation="2">
            <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
            <v-app-bar-title>
                <v-icon class="mr-2" color="white">mdi-shield-check</v-icon>
                <span class="text-white font-weight-bold">PinPointMe Admin</span>
            </v-app-bar-title>
            <v-spacer />
            <!-- Notification Bell -->
            <v-btn icon @click="showNotificationPanel = !showNotificationPanel" class="mr-1">
                <v-badge :content="totalUnreadCount" :model-value="totalUnreadCount > 0" color="error" overlap>
                    <v-icon color="white">mdi-bell</v-icon>
                </v-badge>
            </v-btn>
            <!-- Profile Avatar Menu -->
            <v-menu offset-y>
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props">
                        <v-avatar color="white" size="36">
                            <span class="text-primary font-weight-bold">{{ adminInitials }}</span>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item @click="goToProfile" prepend-icon="mdi-account">
                        <v-list-item-title>Profile</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="toggleDarkMode" prepend-icon="mdi-theme-light-dark">
                        <v-list-item-title>{{ isDark ? 'Light Mode' : 'Dark Mode' }}</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="logout" prepend-icon="mdi-logout">
                        <v-list-item-title>Logout</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Navigation Drawer (Unified) -->
        <v-navigation-drawer
            v-model="drawer"
            :permanent="!isMobile"
            :temporary="isMobile"
            app
        >
            <v-list>
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard" active @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures" @click="closeDrawerOnMobile"></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- ── Notification Center Panel ── -->
        <v-navigation-drawer
            v-model="showNotificationPanel"
            location="right"
            temporary
            :width="isMobile ? 360 : 420"
            class="nc-drawer"
        >
            <!-- ── Header ── -->
            <div class="nc-header">
                <div class="nc-header-inner">
                    <div class="nc-header-icon">
                        <v-icon size="18" color="white">mdi-bell</v-icon>
                    </div>
                    <div>
                        <div class="nc-header-title">Notifications</div>
                        <div class="nc-header-sub">{{ totalUnreadCount > 0 ? `${totalUnreadCount} unread` : 'All caught up' }}</div>
                    </div>
                </div>
                <div class="nc-header-actions">
                    <button v-if="unreadActivityCount > 0" class="nc-mark-read-btn" @click.stop="markAllRead" title="Mark all as read">
                        <v-icon size="16">mdi-check-all</v-icon>
                    </button>
                    <button class="nc-close-btn" @click="showNotificationPanel = false">
                        <v-icon size="18">mdi-close</v-icon>
                    </button>
                </div>
            </div>

            <!-- ── Tabs ── -->
            <div class="nc-tabs">
                <button class="nc-tab" :class="{ 'nc-tab-active': notifTab === 'activity' }" @click="notifTab = 'activity'">
                    <v-icon size="15">mdi-pulse</v-icon>
                    <span>Activity</span>
                    <span v-if="unreadActivityCount > 0" class="nc-badge nc-badge-red">{{ unreadActivityCount }}</span>
                </button>
                <button class="nc-tab" :class="{ 'nc-tab-active': notifTab === 'messages' }" @click="notifTab = 'messages'">
                    <v-icon size="15">mdi-chat-processing-outline</v-icon>
                    <span>Messages</span>
                    <span v-if="unreadMessageBadge > 0" class="nc-badge nc-badge-blue">{{ unreadMessageBadge }}</span>
                </button>
                <div class="nc-tab-slider" :class="{ 'nc-tab-slider-right': notifTab === 'messages' }"></div>
            </div>

            <!-- ─── Activity Tab ─── -->
            <div v-if="notifTab === 'activity'" class="nc-body">
                <!-- Empty state -->
                <div v-if="activityNotifications.length === 0" class="nc-empty">
                    <div class="nc-empty-icon">
                        <v-icon size="32" color="#B0BEC5">mdi-bell-check-outline</v-icon>
                    </div>
                    <p class="nc-empty-title">No activity yet</p>
                    <p class="nc-empty-sub">Rescue request updates will appear here</p>
                </div>

                <!-- Notification items -->
                <div
                    v-for="notif in activityNotifications"
                    :key="notif.id"
                    class="nc-item"
                    :class="{ 'nc-item-unread': !notif.read }"
                    @click="markActivityRead(notif)"
                >
                    <div class="nc-item-bar" :class="`nc-bar-${notif.color}`"></div>
                    <div class="nc-item-icon" :class="`nc-icon-${notif.color}`">
                        <v-icon size="16" color="white">{{ notif.icon }}</v-icon>
                    </div>
                    <div class="nc-item-content">
                        <div class="nc-item-top">
                            <span class="nc-item-title">{{ notif.title }}</span>
                            <span v-if="!notif.read" class="nc-unread-dot"></span>
                        </div>
                        <div class="nc-item-msg">{{ notif.message }}</div>
                        <!-- Detail row: location + rescue code -->
                        <div v-if="notif.request" class="nc-item-detail">
                            <span v-if="notif.request.rescue_code" class="nc-detail-code">
                                <v-icon size="10">mdi-pound</v-icon>
                                {{ notif.request.rescue_code }}
                            </span>
                            <span v-if="notif.request.firstName || notif.request.lastName" class="nc-detail-name">
                                <v-icon size="10">mdi-account</v-icon>
                                {{ `${notif.request.firstName || ''} ${notif.request.lastName || ''}`.trim() }}
                            </span>
                        </div>
                        <div class="nc-item-footer">
                            <span class="nc-item-time">
                                <v-icon size="10">mdi-clock-outline</v-icon>
                                {{ formatTimeAgo(notif.time) }}
                            </span>
                            <!-- Urgency badge -->
                            <span v-if="notif.type === 'pending' && notif.request" class="nc-urgency-chip" :class="`nc-urgency-${(notif.request.urgency_level || 'medium').toLowerCase()}`">
                                {{ (notif.request.urgency_level || 'Medium') }}
                            </span>
                            <!-- Force Alert button -->
                            <button
                                v-if="notif.type === 'pending' && notif.canForceAlert && !notif.forceAlerted"
                                class="nc-force-btn"
                                :class="{ 'nc-force-loading': forceAlertLoading === notif.requestId }"
                                :title="getThresholdLabel(notif.request)"
                                @click.stop="sendForceAlert(notif.request)"
                            >
                                <v-icon size="12">mdi-alarm-light</v-icon>
                                <span>Force Alert</span>
                            </button>
                            <span v-else-if="notif.type === 'pending' && !notif.canForceAlert && !notif.forceAlerted && getForceAlertCountdown(notif.request)" class="nc-countdown-chip">
                                <v-icon size="10">mdi-timer-sand</v-icon>
                                {{ getForceAlertCountdown(notif.request) }}
                            </span>
                            <span v-else-if="notif.forceAlerted" class="nc-alerted-chip">
                                <v-icon size="10">mdi-check</v-icon> Alerted
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── Messages Tab ─── -->
            <div v-if="notifTab === 'messages'" class="nc-body">
                <!-- Empty state -->
                <div v-if="adminConversations.length === 0" class="nc-empty">
                    <div class="nc-empty-icon">
                        <v-icon size="32" color="#B0BEC5">mdi-chat-sleep-outline</v-icon>
                    </div>
                    <p class="nc-empty-title">No conversations</p>
                    <p class="nc-empty-sub">Messages between users and rescuers will appear here</p>
                </div>

                <!-- Conversation cards -->
                <div
                    v-for="conv in adminConversations"
                    :key="conv.id"
                    class="nc-conv"
                    :class="{ 'nc-conv-open': expandedConv === conv.id, 'nc-conv-new': conv._hasNewMsg }"
                >
                    <div class="nc-conv-header" @click="toggleConversation(conv)">
                        <!-- Stacked avatars -->
                        <div class="nc-conv-avatars">
                            <div class="nc-avatar nc-avatar-user">{{ getConvInitials(conv, 'user') }}</div>
                            <div class="nc-avatar nc-avatar-rescuer">{{ getConvInitials(conv, 'rescuer') }}</div>
                        </div>
                        <!-- Info -->
                        <div class="nc-conv-info">
                            <div class="nc-conv-name">{{ getConvParticipantNames(conv) }}</div>
                            <div class="nc-conv-preview" v-if="conv.last_message">
                                <span class="nc-conv-sender">{{ conv.last_message?.sender_name }}:</span>
                                {{ truncate(conv.last_message?.content, 35) }}
                            </div>
                            <div class="nc-conv-tags">
                                <span
                                    v-if="conv.rescue_request?.rescue_code"
                                    class="nc-tag"
                                    :class="`nc-tag-${getStatusColor(conv.rescue_request?.status)}`"
                                >
                                    {{ conv.rescue_request.rescue_code }}
                                </span>
                                <span class="nc-conv-time">{{ formatTimeAgo(conv.updated_at) }}</span>
                            </div>
                        </div>
                        <!-- Right side -->
                        <div class="nc-conv-right">
                            <span v-if="conv.total_messages" class="nc-msg-count">
                                {{ conv.total_messages }}
                                <v-icon size="11">mdi-message-text</v-icon>
                            </span>
                            <v-icon size="16" class="nc-conv-chevron" :class="{ 'nc-chevron-up': expandedConv === conv.id }">
                                mdi-chevron-down
                            </v-icon>
                        </div>
                    </div>

                    <!-- Expanded: Read-only messages -->
                    <v-expand-transition>
                        <div v-if="expandedConv === conv.id" class="nc-conv-body" @click.stop>
                            <div class="nc-conv-body-label">
                                <v-icon size="12">mdi-eye-outline</v-icon>
                                Read-only view
                            </div>
                            <div v-if="loadingMessages" class="nc-conv-loading">
                                <v-progress-circular indeterminate size="22" width="2" color="#3674B5"></v-progress-circular>
                            </div>
                            <div v-else class="nc-msg-list">
                                <div
                                    v-for="msg in expandedMessages"
                                    :key="msg.id"
                                    class="nc-msg"
                                    :class="getParticipantType(conv, msg.sender_id) === 'rescuer' ? 'nc-msg-right' : 'nc-msg-left'"
                                >
                                    <div class="nc-msg-name">{{ msg.sender_name || 'Unknown' }}</div>
                                    <div class="nc-msg-bubble">{{ msg.content }}</div>
                                    <div class="nc-msg-time">{{ formatMsgTime(msg.sent_at) }}</div>
                                </div>
                                <div v-if="expandedMessages.length === 0" class="nc-conv-empty-msg">
                                    No messages yet
                                </div>
                            </div>
                        </div>
                    </v-expand-transition>
                </div>
            </div>
        </v-navigation-drawer>

        <!-- Notification Popup -->
        <NotificationPopup
            :show="popupAlert.show"
            :title="popupAlert.title"
            :message="popupAlert.message"
            :type="popupAlert.type"
            :icon="popupAlert.icon"
            @close="popupAlert.show = false"
            @click="handlePopupClick"
        />

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header with Welcome Message -->
                <div class="page-header mb-4 mb-md-6">
                    <div class="page-header-content">
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold gradient-text">Dashboard Overview</h1>
                        <p class="text-grey mt-1 text-body-2">Welcome back! Here's what's happening with rescue operations.</p>
                    </div>
                    <v-select
                        v-model="timeFilter"
                        :items="timeFilters"
                        item-title="label"
                        item-value="value"
                        variant="outlined"
                        density="compact"
                        rounded="xl"
                        class="time-filter-select"
                        hide-details
                        @update:model-value="refreshData"
                    />
                </div>

                <!-- Stats Cards with Gradient Backgrounds -->
                <v-row>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-primary" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Total Requests</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.total }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-trending-up</v-icon>
                                            All Time
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-alert-circle-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-warning" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Pending</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.pending }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-clock-outline</v-icon>
                                            Awaiting
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-clock-alert-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-info" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">In Progress</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.in_progress }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-run-fast</v-icon>
                                            Active
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-progress-clock</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-success" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Completed</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.completed }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-check-all</v-icon>
                                            Resolved
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-check-circle-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Rescuer Status Cards -->
                <v-row class="mt-4">
                    <v-col cols="12">
                        <v-card rounded="xl" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="primary" size="40" class="mr-3">
                                    <v-icon color="white">mdi-lifebuoy</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">Rescuer Status Overview</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-4">
                                <v-row>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-grey-lighten-4">
                                            <v-avatar color="primary" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-account-group</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-primary">{{ rescuerStats.total }}</h3>
                                            <p class="text-grey text-body-2 mb-0">Total Rescuers</p>
                                        </div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-success-lighten-5">
                                            <v-avatar color="success" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-check-circle</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-success">{{ rescuerStats.available }}</h3>
                                            <p class="text-grey text-body-2 mb-0">Available</p>
                                        </div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-warning-lighten-5">
                                            <v-avatar color="warning" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-run-fast</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-warning">{{ rescuerStats.on_rescue }}</h3>
                                            <p class="text-grey text-body-2 mb-0">On Rescue</p>
                                        </div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-grey-lighten-3">
                                            <v-avatar color="grey" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-power-sleep</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-grey-darken-1">{{ rescuerStats.off_duty }}</h3>
                                            <p class="text-grey text-body-2 mb-0">Off Duty</p>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <v-row class="mt-4">
                    <!-- Rescues by Building -->
                    <v-col cols="12" md="6">
                        <v-card rounded="xl" height="100%" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="info" size="40" class="mr-3">
                                    <v-icon color="white">mdi-office-building</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">Rescues by Building</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-4">
                                <v-list v-if="rescuesByBuilding.length > 0" class="bg-transparent">
                                    <v-list-item 
                                        v-for="(item, index) in rescuesByBuilding" 
                                        :key="item.name"
                                        class="px-0 mb-2"
                                    >
                                        <template v-slot:prepend>
                                            <v-avatar :color="getBuildingColor(index)" size="42" class="mr-3">
                                                <v-icon size="small" color="white">mdi-domain</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="font-weight-medium">{{ item.name }}</v-list-item-title>
                                        <v-list-item-subtitle>
                                            <v-progress-linear 
                                                :model-value="(item.count / Math.max(...rescuesByBuilding.map(b => b.count))) * 100" 
                                                :color="getBuildingColor(index)"
                                                height="6"
                                                rounded
                                                class="mt-1"
                                            ></v-progress-linear>
                                        </v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-chip :color="getBuildingColor(index)" size="small" variant="flat" class="font-weight-bold">
                                                {{ item.count }}
                                            </v-chip>
                                        </template>
                                    </v-list-item>
                                </v-list>
                                <v-alert v-else type="info" variant="tonal" rounded="xl">
                                    <v-icon start>mdi-information</v-icon>
                                    No rescue data available for this period.
                                </v-alert>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- User Statistics -->
                    <v-col cols="12" md="6">
                        <v-card rounded="xl" height="100%" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="purple" size="40" class="mr-3">
                                    <v-icon color="white">mdi-account-multiple</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">User Statistics</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-4">
                                <div class="text-center mb-6">
                                    <div class="d-inline-flex align-center justify-center rounded-circle pa-6 bg-primary-lighten-5">
                                        <div>
                                            <h2 class="text-h2 font-weight-bold text-primary">{{ userStats.total }}</h2>
                                            <p class="text-grey mb-0">Total Users</p>
                                        </div>
                                    </div>
                                </div>
                                <v-row class="text-center">
                                    <v-col cols="4">
                                        <v-card class="pa-3 bg-blue-lighten-5" rounded="xl" flat>
                                            <v-avatar color="blue" size="36" class="mb-2">
                                                <v-icon color="white" size="20">mdi-school</v-icon>
                                            </v-avatar>
                                            <h4 class="text-h5 font-weight-bold text-blue">{{ userStats.by_role?.student || 0 }}</h4>
                                            <p class="text-caption text-grey mb-0">Students</p>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="4">
                                        <v-card class="pa-3 bg-purple-lighten-5" rounded="xl" flat>
                                            <v-avatar color="purple" size="36" class="mb-2">
                                                <v-icon color="white" size="20">mdi-human-male-board</v-icon>
                                            </v-avatar>
                                            <h4 class="text-h5 font-weight-bold text-purple">{{ userStats.by_role?.faculty || 0 }}</h4>
                                            <p class="text-caption text-grey mb-0">Faculty</p>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="4">
                                        <v-card class="pa-3 bg-teal-lighten-5" rounded="xl" flat>
                                            <v-avatar color="teal" size="36" class="mb-2">
                                                <v-icon color="white" size="20">mdi-briefcase</v-icon>
                                            </v-avatar>
                                            <h4 class="text-h5 font-weight-bold text-teal">{{ userStats.by_role?.staff || 0 }}</h4>
                                            <p class="text-caption text-grey mb-0">Staff</p>
                                        </v-card>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Recent Alerts -->
                <v-row class="mt-4">
                    <v-col cols="12">
                        <v-card rounded="xl" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="error" size="40" class="mr-3">
                                    <v-icon color="white">mdi-bell-alert</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">Recent Rescue Alerts</span>
                                <v-spacer />
                                <v-btn variant="tonal" color="primary" href="/admin/reports" rounded="xl" size="small">
                                    <v-icon start size="18">mdi-arrow-right</v-icon>
                                    View All
                                </v-btn>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-0">
                                <v-data-table
                                    :headers="alertHeaders"
                                    :items="recentAlerts"
                                    :items-per-page="5"
                                    class="elevation-0"
                                >
                                    <template v-slot:item.status="{ item }">
                                        <v-chip :color="getStatusColor(item.status)" size="small" variant="flat">
                                            {{ formatStatus(item.status) }}
                                        </v-chip>
                                    </template>
                                    <template v-slot:item.urgency_level="{ item }">
                                        <v-chip :color="getUrgencyColor(item.urgency_level)" size="small" variant="outlined">
                                            {{ item.urgency_level || 'Medium' }}
                                        </v-chip>
                                    </template>
                                    <template v-slot:item.created_at="{ item }">
                                        {{ formatDate(item.created_at) }}
                                    </template>
                                </v-data-table>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { getAllRescueRequests, triggerForceAlert, getAdminConversations, getConversationMessages } from '@/Composables/useApi';
import { setUserActiveStatus } from '@/Utilities/firebase';
import NotificationPopup from '@/Components/NotificationPopup.vue';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

const isDark = ref(false);
const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('v-theme--dark', isDark.value);
};
const goToProfile = () => {
    window.location.href = '/admin/profile';
};
const closeDrawerOnMobile = () => {
    if (isMobile.value) {
        drawer.value = false;
    }
};

const page = usePage();

// Props from Inertia
const props = defineProps({
    statusCounts: {
        type: Object,
        default: () => ({ total: 0, pending: 0, in_progress: 0, completed: 0 })
    },
    rescuesByBuilding: {
        type: Array,
        default: () => []
    },
    rescuerStats: {
        type: Object,
        default: () => ({ total: 0, available: 0, on_rescue: 0, off_duty: 0 })
    },
    recentAlerts: {
        type: Array,
        default: () => []
    },
    userStats: {
        type: Object,
        default: () => ({ total: 0, by_role: { student: 0, faculty: 0, staff: 0 } })
    }
});

const drawer = ref(!mobile.value);
const currentPage = ref('dashboard');
const timeFilter = ref('week');

// Admin Profile
const adminProfile = ref({
    full_name: 'Administrator',
    email: '',
    profile_picture: null
});

const adminInitials = computed(() => {
    if (adminProfile.value.full_name) {
        const names = adminProfile.value.full_name.split(' ');
        if (names.length >= 2) {
            return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
        }
        return names[0][0]?.toUpperCase() || 'AD';
    }
    return 'AD';
});

// Load admin profile from localStorage or page props
const loadAdminProfile = () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (userData.first_name || userData.last_name) {
            adminProfile.value = {
                full_name: `${userData.first_name || ''} ${userData.last_name || ''}`.trim(),
                email: userData.email || '',
                profile_picture: userData.profile_picture || null
            };
        } else if (page.props.auth?.user) {
            const user = page.props.auth.user;
            adminProfile.value = {
                full_name: `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || 'Administrator',
                email: user.email || '',
                profile_picture: user.profile_picture || null
            };
        }
    } catch (e) {
        console.error('Error loading admin profile:', e);
    }
};

const timeFilters = [
    { label: 'Today', value: 'day' },
    { label: 'This Week', value: 'week' },
    { label: 'This Month', value: 'month' },
    { label: 'This Year', value: 'year' },
];

const alertHeaders = [
    { title: 'Code', key: 'rescue_code' },
    { title: 'Requester', key: 'requester_name' },
    { title: 'Location', key: 'location' },
    { title: 'Status', key: 'status' },
    { title: 'Urgency', key: 'urgency_level' },
    { title: 'Date', key: 'created_at' },
];

const statusCounts = ref(props.statusCounts);
const rescuesByBuilding = ref(props.rescuesByBuilding);
const rescuerStats = ref(props.rescuerStats);
const recentAlerts = ref(props.recentAlerts);
const userStats = ref(props.userStats);

const refreshData = async () => {
    try {
        const response = await fetch(`/admin/dashboard?time_filter=${timeFilter.value}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            statusCounts.value = data.data.statusCounts;
            rescuesByBuilding.value = data.data.rescuesByBuilding;
            rescuerStats.value = data.data.rescuerStats;
            recentAlerts.value = data.data.recentAlerts;
            userStats.value = data.data.userStats;
        }
    } catch (error) {
        console.error('Error refreshing data:', error);
    }
};

const logout = async () => {
    // Set user as inactive in Firebase (keep FCM token for offline notifications)
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (userData.id) {
            await setUserActiveStatus(userData.id, false);
            console.log('[Logout] User marked as inactive in Firebase');
        }
    } catch (e) {
        console.error('[Logout] Error setting user inactive:', e);
    }

    // Clear local storage
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    
    // Post logout using fetch and redirect
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            credentials: 'include'
        });
    } catch (e) {
        console.error('Logout error:', e);
    }
    
    // Force redirect to login
    window.location.href = '/login';
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'in_progress': 'info',
        'en_route': 'info',
        'rescued': 'success',
        'completed': 'success',
        'cancelled': 'error'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    return status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
};

const getUrgencyColor = (urgency) => {
    const colors = {
        'Critical': 'error',
        'High': 'orange',
        'Medium': 'warning',
        'Low': 'success'
    };
    return colors[urgency] || 'grey';
};

const getBuildingColor = (index) => {
    const colors = ['primary', 'info', 'success', 'warning', 'purple', 'teal', 'orange', 'pink'];
    return colors[index % colors.length];
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    // Load admin profile data
    loadAdminProfile();
    // Start polling for rescue requests + messages
    fetchPendingRequests();
    fetchAdminConversations();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});

// ── Notification system ────────────────────────────────────────
const { playNotificationSound, vibrate, notifyEmergency } = useNotificationAlert();

const showNotificationPanel = ref(false);
const notifTab = ref('activity');
const pendingRequests = ref([]);
const previousPendingCount = ref(0);
const previousPendingIds = ref([]);
const forceAlertLoading = ref(null);
const notifiedThresholdIds = ref(new Set());  // Track which requests already triggered urgency-based admin alert
let pollingInterval = null;
const POLLING_INTERVAL = 8000;

// Activity notifications — persistent, with read/unread state
const loadSavedNotifications = () => {
    try {
        const saved = JSON.parse(localStorage.getItem('adminActivityNotifs') || '[]');
        return Array.isArray(saved) ? saved : [];
    } catch { return []; }
};
const activityNotifications = ref(loadSavedNotifications());
const readNotifIds = ref(new Set(JSON.parse(localStorage.getItem('adminReadNotifs') || '[]')));

// Apply saved read state to loaded notifications
activityNotifications.value.forEach(n => {
    if (readNotifIds.value.has(n.id)) n.read = true;
});

const saveNotifications = () => {
    try {
        // Save up to 100, strip heavy request objects to save space
        const toSave = activityNotifications.value.slice(0, 100).map(n => ({
            ...n,
            request: n.request ? {
                id: n.request.id,
                rescue_code: n.request.rescue_code,
                urgency_level: n.request.urgency_level,
                force_alert: n.request.force_alert,
                created_at: n.request.created_at,
                firstName: n.request.firstName,
                lastName: n.request.lastName,
                status: n.request.status,
            } : null,
        }));
        localStorage.setItem('adminActivityNotifs', JSON.stringify(toSave));
    } catch { /* storage full — ignore */ }
};

// Conversations state
const adminConversations = ref([]);
const previousConvMessageCounts = ref({});
const expandedConv = ref(null);
const expandedMessages = ref([]);
const loadingMessages = ref(false);

const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'error',
    icon: 'mdi-bell',
    callback: null,
});

// ── Computed counts ────────────────────────────────────────────
const unreadActivityCount = computed(() =>
    activityNotifications.value.filter(n => !n.read).length
);

const unreadMessageBadge = computed(() => {
    // Count conversations with new messages since last check
    return adminConversations.value.filter(c => c._hasNewMsg).length;
});

const totalUnreadCount = computed(() =>
    unreadActivityCount.value + unreadMessageBadge.value
);

const pendingAlertCount = computed(() => pendingRequests.value.length);

const pendingTooLongRequests = computed(() =>
    pendingRequests.value.filter(r => canForceAlertByUrgency(r))
);

// ── Helpers ────────────────────────────────────────────────────
const URGENCY_THRESHOLDS = {
    critical: 10,   // 10 seconds
    high:     30,   // 30 seconds
    medium:   120,  // 2 minutes
    low:      300,  // 5 minutes
};

const getThresholdSeconds = (request) => {
    const urgency = (request.urgency_level || 'medium').toLowerCase();
    return URGENCY_THRESHOLDS[urgency] ?? 120;
};

const getElapsedSeconds = (request) => {
    if (!request.created_at) return 0;
    return Math.floor((Date.now() - new Date(request.created_at).getTime()) / 1000);
};

const canForceAlertByUrgency = (request) => {
    if (!request?.created_at) return false;
    return getElapsedSeconds(request) >= getThresholdSeconds(request);
};

const getForceAlertCountdown = (request) => {
    if (!request?.created_at) return '';
    const remaining = getThresholdSeconds(request) - getElapsedSeconds(request);
    if (remaining <= 0) return '';
    if (remaining >= 60) return `${Math.ceil(remaining / 60)}m`;
    return `${remaining}s`;
};

const getThresholdLabel = (request) => {
    const secs = getThresholdSeconds(request);
    const urgency = (request.urgency_level || 'medium').toLowerCase();
    const label = secs >= 60 ? `${secs / 60} min` : `${secs}s`;
    return `${urgency.charAt(0).toUpperCase() + urgency.slice(1)} — notify after ${label}`;
};

const getReqLocation = (req) => {
    const parts = [];
    if (req.building?.name) parts.push(req.building.name);
    if (req.floor?.floor_name) parts.push(req.floor.floor_name);
    if (req.room?.room_name) parts.push(req.room.room_name);
    return parts.join(' › ') || 'Unknown Location';
};

const formatTimeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffHr = Math.floor(diffMin / 60);
    if (diffHr < 24) return `${diffHr}h ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const formatMsgTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const truncate = (str, len) => {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '…' : str;
};

// ── Activity notification helpers ──────────────────────────────
const addActivityNotification = (id, title, message, icon, color, type = 'info', request = null) => {
    // Don't add duplicates
    if (activityNotifications.value.find(n => n.id === id)) return;
    activityNotifications.value.unshift({
        id,
        title,
        message,
        icon,
        color,
        type,
        time: new Date().toISOString(),
        read: readNotifIds.value.has(id),
        request,
        requestId: request?.id || null,
        canForceAlert: type === 'pending' && request && canForceAlertByUrgency(request),
        forceAlerted: request?.force_alert || false,
    });
    // Keep max 100 notifications
    if (activityNotifications.value.length > 100) {
        activityNotifications.value = activityNotifications.value.slice(0, 100);
    }
    saveNotifications();
};

const markActivityRead = (notif) => {
    notif.read = true;
    readNotifIds.value.add(notif.id);
    localStorage.setItem('adminReadNotifs', JSON.stringify([...readNotifIds.value]));
    saveNotifications();
};

const markAllRead = () => {
    activityNotifications.value.forEach(n => {
        n.read = true;
        readNotifIds.value.add(n.id);
    });
    localStorage.setItem('adminReadNotifs', JSON.stringify([...readNotifIds.value]));
    saveNotifications();
};

// ── Conversation helpers ───────────────────────────────────────
const getConvParticipantNames = (conv) => {
    if (!conv.participants) return 'Unknown';
    const names = conv.participants.map(p => {
        const u = p.user;
        return u ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : 'Unknown';
    });
    return names.join(' & ');
};

const getConvInitials = (conv, type) => {
    const p = conv.participants?.find(p => p.participant_type === type);
    if (p?.user) {
        return `${(p.user.first_name || '?')[0]}${(p.user.last_name || '?')[0]}`.toUpperCase();
    }
    return type === 'user' ? 'U' : 'R';
};

const getConvColor = (conv, type) => {
    return type === 'user' ? '#3674B5' : '#185D33';
};

const getParticipantType = (conv, senderId) => {
    const p = conv.participants?.find(p => p.user_id === senderId || p.user?.id === senderId);
    return p?.participant_type || 'user';
};

const toggleConversation = async (conv) => {
    if (expandedConv.value === conv.id) {
        expandedConv.value = null;
        expandedMessages.value = [];
        return;
    }
    expandedConv.value = conv.id;
    conv._hasNewMsg = false; // mark as seen
    loadingMessages.value = true;
    try {
        const response = await getConversationMessages(conv.id);
        const msgs = Array.isArray(response) ? response : (response?.data || []);
        expandedMessages.value = msgs;
    } catch (e) {
        console.error('Error loading messages:', e);
        expandedMessages.value = [];
    } finally {
        loadingMessages.value = false;
    }
};

// ── Popup click handler ────────────────────────────────────────
const handlePopupClick = () => {
    popupAlert.value.show = false;
    if (popupAlert.value.callback) {
        popupAlert.value.callback();
    }
};

// ── Polling: Rescue requests ───────────────────────────────────
const fetchPendingRequests = async () => {
    try {
        const response = await getAllRescueRequests();
        const data = response?.data || response;
        const all = Array.isArray(data) ? data : (data?.data || []);

        // Filter pending requests
        const pending = all.filter(r => r.status === 'pending');

        // Detect new requests
        const currentIds = pending.map(r => r.id);
        const newIds = currentIds.filter(id => !previousPendingIds.value.includes(id));

        if (newIds.length > 0 && previousPendingIds.value.length > 0) {
            const newReq = pending.find(r => r.id === newIds[0]);
            const name = newReq ? `${newReq.firstName || ''} ${newReq.lastName || ''}`.trim() : 'Someone';
            const location = newReq ? getReqLocation(newReq) : 'Unknown';

            popupAlert.value = {
                show: true,
                title: `New Rescue Request`,
                message: `${name} needs help at ${location}`,
                type: 'error',
                icon: 'mdi-alert-circle',
                callback: () => { showNotificationPanel.value = true; notifTab.value = 'activity'; },
            };
            playNotificationSound('message');
            vibrate([200, 100, 200]);
            setTimeout(() => { popupAlert.value.show = false; }, 10000);
        }

        // Build activity notifications from ALL rescue requests (not just pending)
        all.forEach(req => {
            const name = `${req.firstName || ''} ${req.lastName || ''}`.trim() || 'Someone';
            const location = getReqLocation(req);
            const status = req.status;

            if (status === 'pending') {
                addActivityNotification(
                    `rescue-pending-${req.id}`,
                    `${name} needs help!`,
                    `📍 ${location}`,
                    'mdi-alert-circle',
                    'warning',
                    'pending',
                    req
                );
            } else if (status === 'accepted' || status === 'in_progress' || status === 'en_route') {
                addActivityNotification(
                    `rescue-progress-${req.id}`,
                    `Rescue in progress`,
                    `${name} — ${location}`,
                    'mdi-run-fast',
                    'info',
                    'progress',
                    req
                );
            } else if (status === 'rescued' || status === 'completed') {
                addActivityNotification(
                    `rescue-done-${req.id}`,
                    `Rescue completed`,
                    `${name} has been rescued at ${location}`,
                    'mdi-check-circle',
                    'success',
                    'completed',
                    req
                );
            }
        });

        // Update force-alert status on existing notifs
        activityNotifications.value.forEach(n => {
            if (n.type === 'pending' && n.request) {
                const fresh = pending.find(r => r.id === n.requestId);
                if (fresh) {
                    n.canForceAlert = canForceAlertByUrgency(fresh);
                    n.forceAlerted = fresh.force_alert || false;
                    n.request = fresh;
                }
            }
        });
        saveNotifications();

        // Auto-notify admin when a pending request exceeds its urgency threshold
        // Critical: 10s, High: 30s, Medium: 2min, Low: 5min
        pending.forEach(req => {
            if (
                canForceAlertByUrgency(req) &&
                !req.force_alert &&
                !notifiedThresholdIds.value.has(req.id)
            ) {
                notifiedThresholdIds.value.add(req.id);
                const name = `${req.firstName || ''} ${req.lastName || ''}`.trim() || 'Someone';
                const location = getReqLocation(req);
                const urgency = (req.urgency_level || 'Medium');
                const secs = getThresholdSeconds(req);
                const waitLabel = secs >= 60 ? `${secs / 60} minute(s)` : `${secs} seconds`;

                popupAlert.value = {
                    show: true,
                    title: `⚠️ No Response — ${urgency} Urgency`,
                    message: `It's been ${waitLabel}, still no rescuer for ${name} at ${location}. You may now send a Force Alert.`,
                    type: 'warning',
                    icon: 'mdi-timer-alert',
                    callback: () => { showNotificationPanel.value = true; notifTab.value = 'activity'; },
                };
                playNotificationSound('message');
                vibrate([200, 100, 200]);
                setTimeout(() => { popupAlert.value.show = false; }, 12000);
            }
        });

        previousPendingIds.value = currentIds;
        previousPendingCount.value = pending.length;
        pendingRequests.value = pending;
    } catch (err) {
        console.error('Error fetching pending requests:', err);
    }
};

// ── Polling: Admin conversations ───────────────────────────────
const fetchAdminConversations = async () => {
    try {
        const response = await getAdminConversations();
        const convs = Array.isArray(response) ? response : (response?.data || []);

        // Detect new messages by comparing message counts
        convs.forEach(conv => {
            const prevCount = previousConvMessageCounts.value[conv.id] || 0;
            const currentCount = conv.total_messages || 0;
            if (currentCount > prevCount && prevCount > 0) {
                conv._hasNewMsg = true;

                // Add activity notification for new message
                const lastMsg = conv.last_message;
                const senderName = lastMsg?.sender_name || 'Someone';
                addActivityNotification(
                    `msg-${conv.id}-${currentCount}`,
                    `New message from ${senderName}`,
                    `${truncate(lastMsg?.content, 60)}`,
                    'mdi-message-text',
                    'primary',
                    'message'
                );

                // Show popup for new messages
                if (!showNotificationPanel.value) {
                    popupAlert.value = {
                        show: true,
                        title: `New Message`,
                        message: `${senderName}: ${truncate(lastMsg?.content, 50)}`,
                        type: 'info',
                        icon: 'mdi-message-text',
                        callback: () => { showNotificationPanel.value = true; notifTab.value = 'messages'; },
                    };
                    playNotificationSound('message');
                    vibrate([100, 50, 100]);
                    setTimeout(() => { popupAlert.value.show = false; }, 6000);
                }
            } else {
                // Preserve existing _hasNewMsg flag
                const existing = adminConversations.value.find(c => c.id === conv.id);
                conv._hasNewMsg = existing?._hasNewMsg || false;
            }
            previousConvMessageCounts.value[conv.id] = currentCount;
        });

        adminConversations.value = convs;
    } catch (err) {
        console.error('Error fetching admin conversations:', err);
    }
};

const sendForceAlert = async (request) => {
    forceAlertLoading.value = request.id;
    try {
        await triggerForceAlert(request.id);
        request.force_alert = true;
        request.force_alert_at = new Date().toISOString();

        // Update the notification item
        const notif = activityNotifications.value.find(n => n.requestId === request.id);
        if (notif) notif.forceAlerted = true;

        popupAlert.value = {
            show: true,
            title: 'Force Alert Sent',
            message: `Available rescuers will receive an unstoppable ringtone. Rescuers with ongoing rescues will only get a normal notification.`,
            type: 'success',
            icon: 'mdi-check-circle',
            callback: null,
        };
        setTimeout(() => { popupAlert.value.show = false; }, 5000);
    } catch (err) {
        console.error('Error sending force alert:', err);
        const backendMsg = err.data?.message || '';
        popupAlert.value = {
            show: true,
            title: backendMsg.includes('wait') ? 'Too Early' : 'Error',
            message: backendMsg || err.message || 'Failed to send force alert',
            type: backendMsg.includes('wait') ? 'warning' : 'error',
            icon: backendMsg.includes('wait') ? 'mdi-timer-sand' : 'mdi-alert',
            callback: null,
        };
        setTimeout(() => { popupAlert.value.show = false; }, 6000);
    } finally {
        forceAlertLoading.value = null;
    }
};

const startPolling = () => {
    if (pollingInterval) return;
    pollingInterval = setInterval(() => {
        fetchPendingRequests();
        fetchAdminConversations();
    }, POLLING_INTERVAL);
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};
</script>

<style scoped>
/* ══════════════════════════════════════════════
   NOTIFICATION CENTER — PinPointMe Branded
   ══════════════════════════════════════════════ */

/* Drawer shell */
.nc-drawer {
    background: #f4f6f9 !important;
    border-left: 1px solid #e8ecf0 !important;
}

/* ── Header ── */
.nc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%);
}

.nc-header-inner {
    display: flex;
    align-items: center;
    gap: 12px;
}

.nc-header-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
}

.nc-header-title {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.2px;
}

.nc-header-sub {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 1px;
}

.nc-header-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

.nc-mark-read-btn,
.nc-close-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s ease;
}

.nc-mark-read-btn:hover,
.nc-close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

/* ── Tabs ── */
.nc-tabs {
    display: flex;
    gap: 0;
    padding: 12px 16px 0;
    background: #ffffff;
    border-bottom: 1px solid #e8ecf0;
    position: relative;
}

.nc-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px 12px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 600;
    color: #90A4AE;
    cursor: pointer;
    transition: color 0.2s ease;
    position: relative;
    z-index: 1;
}

.nc-tab-active {
    color: #3674B5;
}

.nc-tab:hover:not(.nc-tab-active) {
    color: #546E7A;
}

.nc-tab-slider {
    position: absolute;
    bottom: 0;
    left: 16px;
    width: calc(50% - 16px);
    height: 2.5px;
    background: #3674B5;
    border-radius: 2px 2px 0 0;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.nc-tab-slider-right {
    transform: translateX(100%);
}

.nc-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    font-size: 10px;
    font-weight: 700;
    padding: 0 5px;
    color: #ffffff;
    line-height: 1;
}

.nc-badge-red {
    background: #b71c1c;
}

.nc-badge-blue {
    background: #3674B5;
}

/* ── Body / List container ── */
.nc-body {
    overflow-y: auto;
    max-height: calc(100vh - 140px);
    padding: 12px 14px;
}

/* ── Empty state ── */
.nc-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60px 24px;
    text-align: center;
}

.nc-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #eef1f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.nc-empty-title {
    font-size: 14px;
    font-weight: 600;
    color: #546E7A;
    margin: 0 0 4px;
}

.nc-empty-sub {
    font-size: 12.5px;
    color: #90A4AE;
    margin: 0;
}

/* ═══════════════════════════════════════════
   Activity Notification Items
   ═══════════════════════════════════════════ */
.nc-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    margin-bottom: 8px;
    background: #ffffff;
    border-radius: 14px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    border: 1px solid transparent;
}

.nc-item:hover {
    background: #fafbfd;
    box-shadow: 0 2px 10px rgba(54, 116, 181, 0.07);
    transform: translateY(-1px);
}

.nc-item-unread {
    background: #f0f6ff;
    border-color: rgba(54, 116, 181, 0.1);
}

.nc-item-unread:hover {
    background: #e8f0fc;
}

/* Left accent bar */
.nc-item-bar {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3.5px;
    border-radius: 0 3px 3px 0;
}

.nc-bar-warning { background: linear-gradient(180deg, #DFA92C, #c9941f); }
.nc-bar-info    { background: linear-gradient(180deg, #42A5F5, #3674B5); }
.nc-bar-success { background: linear-gradient(180deg, #66BB6A, #185D33); }
.nc-bar-error   { background: linear-gradient(180deg, #EF5350, #b71c1c); }
.nc-bar-primary { background: linear-gradient(180deg, #42A5F5, #3674B5); }

/* Icon circle */
.nc-item-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.nc-icon-warning { background: linear-gradient(135deg, #FFA726, #DFA92C); }
.nc-icon-info    { background: linear-gradient(135deg, #42A5F5, #3674B5); }
.nc-icon-success { background: linear-gradient(135deg, #66BB6A, #2E7D32); }
.nc-icon-error   { background: linear-gradient(135deg, #EF5350, #b71c1c); }
.nc-icon-primary { background: linear-gradient(135deg, #42A5F5, #3674B5); }

/* Content */
.nc-item-content {
    flex: 1;
    min-width: 0;
}

.nc-item-top {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 3px;
}

.nc-item-title {
    font-size: 13px;
    font-weight: 650;
    color: #13294B;
    line-height: 1.35;
    flex: 1;
    word-break: break-word;
}

.nc-unread-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #3674B5;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(54, 116, 181, 0.2);
    margin-top: 2px;
}

.nc-item-msg {
    font-size: 12px;
    color: #546E7A;
    line-height: 1.45;
    word-break: break-word;
}

.nc-item-detail {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 5px;
}

.nc-detail-code,
.nc-detail-name {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    color: #78909C;
    background: rgba(54, 116, 181, 0.06);
    padding: 1px 7px;
    border-radius: 5px;
}

.nc-item-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 6px;
}

.nc-item-time {
    font-size: 11px;
    color: #90A4AE;
    display: flex;
    align-items: center;
    gap: 3px;
}

/* Force Alert button */
.nc-force-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #EF5350, #C62828);
    color: #ffffff;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
    box-shadow: 0 2px 6px rgba(198, 40, 40, 0.25);
}

.nc-force-btn:hover {
    transform: scale(1.04);
    box-shadow: 0 3px 10px rgba(198, 40, 40, 0.35);
}

.nc-force-loading {
    opacity: 0.6;
    pointer-events: none;
}

.nc-alerted-chip {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(198, 40, 40, 0.08);
    color: #C62828;
    font-size: 10.5px;
    font-weight: 600;
}

.nc-countdown-chip {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(255, 152, 0, 0.1);
    color: #E65100;
    font-size: 10.5px;
    font-weight: 600;
}

.nc-urgency-chip {
    display: inline-flex;
    align-items: center;
    padding: 1px 7px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.nc-urgency-critical { background: rgba(183, 28, 28, 0.1); color: #b71c1c; }
.nc-urgency-high     { background: rgba(230, 81, 0, 0.1);  color: #E65100; }
.nc-urgency-medium   { background: rgba(255, 152, 0, 0.1); color: #E65100; }
.nc-urgency-low      { background: rgba(46, 125, 50, 0.1); color: #2E7D32; }

/* ═══════════════════════════════════════════
   Conversation Cards (Messages Tab)
   ═══════════════════════════════════════════ */
.nc-conv {
    background: #ffffff;
    border-radius: 14px;
    margin-bottom: 8px;
    overflow: hidden;
    border: 1px solid transparent;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.nc-conv:hover {
    box-shadow: 0 2px 10px rgba(54, 116, 181, 0.07);
}

.nc-conv-open {
    border-color: rgba(54, 116, 181, 0.15);
    box-shadow: 0 4px 16px rgba(54, 116, 181, 0.1);
}

.nc-conv-new {
    border-color: rgba(54, 116, 181, 0.2);
    background: #f8fbff;
}

.nc-conv-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    cursor: pointer;
    transition: background 0.15s ease;
}

.nc-conv-header:hover {
    background: #fafbfd;
}

/* Overlapping avatars */
.nc-conv-avatars {
    position: relative;
    width: 46px;
    height: 36px;
    flex-shrink: 0;
}

.nc-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: #ffffff;
    position: absolute;
    border: 2.5px solid #ffffff;
}

.nc-avatar-user {
    background: linear-gradient(135deg, #42A5F5, #3674B5);
    left: 0;
    top: 0;
    z-index: 2;
}

.nc-avatar-rescuer {
    background: linear-gradient(135deg, #66BB6A, #185D33);
    left: 16px;
    top: 4px;
    z-index: 1;
}

.nc-conv-info {
    flex: 1;
    min-width: 0;
}

.nc-conv-name {
    font-size: 13px;
    font-weight: 650;
    color: #13294B;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

.nc-conv-preview {
    font-size: 12px;
    color: #78909C;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}

.nc-conv-sender {
    font-weight: 600;
    color: #546E7A;
}

.nc-conv-tags {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 5px;
}

.nc-tag {
    display: inline-block;
    padding: 1px 7px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.4px;
}

.nc-tag-warning { background: rgba(223, 169, 44, 0.12); color: #c9941f; }
.nc-tag-info    { background: rgba(54, 116, 181, 0.1);  color: #3674B5; }
.nc-tag-success { background: rgba(24, 93, 51, 0.1);    color: #185D33; }
.nc-tag-error   { background: rgba(183, 28, 28, 0.1);   color: #b71c1c; }
.nc-tag-grey    { background: rgba(0, 0, 0, 0.06);      color: #78909C; }

.nc-conv-time {
    font-size: 11px;
    color: #90A4AE;
}

.nc-conv-right {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}

.nc-msg-count {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 8px;
    border-radius: 8px;
    background: rgba(54, 116, 181, 0.08);
    color: #3674B5;
    font-size: 11px;
    font-weight: 700;
}

.nc-conv-chevron {
    color: #B0BEC5;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.nc-chevron-up {
    transform: rotate(180deg);
}

/* ── Expanded conversation body ── */
.nc-conv-body {
    border-top: 1px solid #eef1f5;
    background: #f8f9fb;
}

.nc-conv-body-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 7px 12px;
    font-size: 10.5px;
    font-weight: 600;
    color: #90A4AE;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f0f2f5;
}

.nc-conv-loading {
    display: flex;
    justify-content: center;
    padding: 20px;
}

.nc-msg-list {
    padding: 12px;
    max-height: 320px;
    overflow-y: auto;
}

/* Message bubbles */
.nc-msg {
    max-width: 82%;
    margin-bottom: 8px;
}

.nc-msg-left {
    margin-right: auto;
}

.nc-msg-right {
    margin-left: auto;
}

.nc-msg-name {
    font-size: 10.5px;
    font-weight: 650;
    margin-bottom: 3px;
    padding: 0 4px;
}

.nc-msg-left .nc-msg-name {
    color: #3674B5;
}

.nc-msg-right .nc-msg-name {
    color: #185D33;
    text-align: right;
}

.nc-msg-bubble {
    padding: 9px 14px;
    border-radius: 14px;
    font-size: 13px;
    color: #263238;
    line-height: 1.45;
    word-break: break-word;
}

.nc-msg-left .nc-msg-bubble {
    background: #ffffff;
    border: 1px solid #e8ecf0;
    border-bottom-left-radius: 4px;
}

.nc-msg-right .nc-msg-bubble {
    background: linear-gradient(135deg, #e8f5e9, #dcedc8);
    border-bottom-right-radius: 4px;
}

.nc-msg-time {
    font-size: 10px;
    color: #90A4AE;
    margin-top: 3px;
    padding: 0 4px;
}

.nc-msg-left .nc-msg-time {
    text-align: left;
}

.nc-msg-right .nc-msg-time {
    text-align: right;
}

.nc-conv-empty-msg {
    text-align: center;
    font-size: 12.5px;
    color: #90A4AE;
    padding: 16px 0;
}

/* ── Existing styles ── */

/* Gradient App Bar */
.gradient-app-bar {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}

/* Gradient Drawer Header */
.gradient-drawer-header {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
}

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #1976D2, #0D47A1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Stat Cards with Gradient Backgrounds */
.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-card-overlay {
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.stat-card-primary {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}

.stat-card-warning {
    background: linear-gradient(135deg, #FB8C00 0%, #F57C00 50%, #EF6C00 100%) !important;
}

.stat-card-info {
    background: linear-gradient(135deg, #00ACC1 0%, #0097A7 50%, #00838F 100%) !important;
}

.stat-card-success {
    background: linear-gradient(135deg, #43A047 0%, #388E3C 50%, #2E7D32 100%) !important;
}

/* Background colors for stats */
.bg-success-lighten-5 {
    background-color: rgba(76, 175, 80, 0.08) !important;
}

.bg-warning-lighten-5 {
    background-color: rgba(255, 152, 0, 0.08) !important;
}

.bg-primary-lighten-5 {
    background-color: rgba(25, 118, 210, 0.08) !important;
}

.bg-blue-lighten-5 {
    background-color: rgba(33, 150, 243, 0.1) !important;
}

.bg-purple-lighten-5 {
    background-color: rgba(156, 39, 176, 0.1) !important;
}

.bg-teal-lighten-5 {
    background-color: rgba(0, 150, 136, 0.1) !important;
}

/* Text colors */
.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}

.opacity-80 {
    opacity: 0.8;
}

/* Card hover effect */
.v-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.v-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

/* Stat cards don't need hover effect */
.stat-card:hover {
    transform: translateY(-4px);
}

/* Page Header Responsive Styles */
.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.page-header-content {
    flex: 1;
    min-width: 200px;
}

.time-filter-select {
    max-width: 150px;
    flex-shrink: 0;
}

/* Mobile Specific Styles */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header-content {
        width: 100%;
    }
    
    .time-filter-select {
        width: 100%;
        max-width: 100%;
    }
}
</style>

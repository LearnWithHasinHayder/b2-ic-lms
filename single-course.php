<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php the_post(); ?>
    <title><?php the_title(); ?> - <?php bloginfo('name'); ?></title>
    <?php
    show_admin_bar(false);
    wp_head();
    ?>
</head>

<body class="bg-gray-100 text-gray-800" x-data="coursePlayer()">
    <!-- Top Admin Bar -->
    <div id="admin-bar" class="w-full bg-black text-white flex items-center justify-between px-4 py-2 text-sm fixed top-0 left-0 z-50" style="height: 44px">
        <div class="flex items-center space-x-10 md:space-x-0">
            <!-- hamburger icon  -->
            <button id="hamburger-btn" @click="toggleSidebar()" class="fixed mr-2 left-4- z-50- bg-white text-white border border-gray-200 rounded-md p-1 shadow-lg- flex items-center justify-center md:hidden" aria-label="Open sidebar" :style="isMobile() ? 'display: flex' : 'display: none'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#000" class="bi bi-list" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
                </svg>
            </button>
            <span class="font-bold text-lg tracking-wide" x-text="courseData?.course?.title || '<?php echo esc_js(get_the_title()); ?>'"></span>
        </div>
        <div class="flex items-center space-x-6">
            <span id="admin-bar-progress" class="flex items-center"><i class="fas fa-chart-line mr-1"></i> <span id="admin-bar-percentage" x-text="progressPercentage + '%'"></span> Complete</span>
            <span id="admin-bar-username" class="flex items-center"><i class="fas fa-user-circle mr-1"></i> <?php echo esc_html(wp_get_current_user()->display_name ?: 'Guest'); ?></span>
            <button id="theme-toggle-btn" @click="toggleTheme()" aria-label="Toggle light/dark mode" class="ml-4 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded px-2 py-1 flex items-center">
                <span id="theme-toggle-icon" class="fa" :class="isDark ? 'fa-sun' : 'fa-moon'"></span>
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="!courseData" class="dashboard flex items-center justify-center h-screen">
        <div class="text-center">
            <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Loading course...</p>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div x-show="courseData" class="dashboard">
        <!-- Left Sidebar -->
        <aside id="sidebar" class="w-full md:w-1/3 lg:w-1/4 xl:w-1/5 h-full flex flex-col border-r border-gray-200 shadow-lg relative" style="min-width: 180px; max-width: 600px" :class="{'open': sidebarOpen}">

            <div class="sidebar-content">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Course Content</h1>
                        <p class="text-sm text-gray-500" x-text="courseData?.course?.description || 'Getting Started with Design'"></p>
                    </div>
                    <button id="sidebar-close-btn" @click="closeSidebar()" class="md:hidden ml-2 p-2 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400" aria-label="Close sidebar" :style="isMobile() ? 'display: block' : 'display: none'">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>

                <div class="flex-grow overflow-y-auto sidebar-scrollbar">
                    <!-- Loading State -->
                    <template x-if="!courseData">
                        <div class="p-4 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                            <p class="text-sm text-gray-500">Loading course content...</p>
                        </div>
                    </template>

                    <!-- Chapters and Videos -->
                    <template x-if="courseData && courseData.course && courseData.course.chapters">
                        <template x-for="chapter in courseData.course.chapters" :key="chapter.id">
                            <div class="chapter border-b border-gray-200" :class="{'open': chapter.isOpen}">
                                <div class="chapter-header flex justify-between items-center p-4 cursor-pointer hover:bg-gray-50" @click="toggleChapter(chapter)">
                                    <h2 class="text-lg font-semibold text-gray-800" x-text="chapter.title"></h2>
                                    <i class="fas transform transition-transform" :class="chapter.isOpen ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                                </div>

                                <div class="chapter-content">
                                    <ul class="videos-list">
                                        <template x-for="video in chapter.videos" :key="video.id">
                                            <li class="video flex items-start p-4 cursor-pointer hover:bg-blue-50 transition-colors duration-200" :class="{'active': currentVideo?.id === video.id}" @click="selectVideo(video)">

                                                <span class="text-lg font-bold text-blue-600 mr-4" x-text="video.number"></span>

                                                <div class="flex-grow">
                                                    <h3 class="font-medium text-gray-800" x-text="video.title"></h3>
                                                    <span class="text-sm text-gray-500" x-text="video.duration"></span>
                                                </div>

                                                <i class="far fa-bookmark text-gray-400 hover:text-blue-600 ml-4 mt-1 cursor-pointer" :class="{'fas text-blue-600': video.isBookmarked, 'far': !video.isBookmarked}" @click.stop="toggleBookmark(video)"></i>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div id="content-display-wrapper" class="flex-grow overflow-y-auto">
                <div id="content-display">
                    <!-- Content will be loaded here -->
                    <template x-if="currentVideo">
                        <div>
                            <template x-if="currentVideo.contentType === 'video'">
                                <div>
                                    <div class="video-container bg-black rounded-lg overflow-hidden shadow-lg">
                                        <iframe id="youtube-player" :src="getYouTubeEmbedUrl(currentVideo.videoUrl)" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                    </div>
                                    <h2 class="text-2xl font-bold mt-6 mb-2 px-4 md:px-8" x-text="currentVideo.title"></h2>
                                    <div class="text-gray-600 text-base px-4 md:px-8 mb-6" x-text="currentVideo.description || ''"></div>
                                    <template x-if="currentVideo.content">
                                        <div class="video-notes-section px-4 md:px-8 mb-8" x-html="currentVideo.content"></div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="currentVideo.contentType === 'quiz' && currentQuizQuestion">
                                <div class="p-8 max-w-4xl mx-auto">
                                    <div class="mb-4 text-gray-500">
                                        <span x-text="quiz.currentIndex + 1"></span> / <span x-text="quiz.questions.length"></span>
                                    </div>

                                    <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-8" x-text="currentQuizQuestion.question"></h2>

                                    <div class="space-y-4 mb-8">
                                        <template x-for="(option, index) in currentQuizQuestion.options" :key="index">
                                            <div @click="handleQuizOptionClick(option)"
                                                 class="flex items-center p-4 rounded-lg border cursor-pointer transition-all duration-200 relative overflow-hidden"
                                                 :class="{
                                                    'hover:bg-gray-50 bg-gray-50 border-gray-200': !quiz.answers[quiz.currentIndex],
                                                    'bg-green-50 border-green-500 text-green-800': quiz.answers[quiz.currentIndex] && isQuizOptionCorrect(option),
                                                    'bg-red-50 border-red-500 text-red-800': quiz.answers[quiz.currentIndex] && quiz.answers[quiz.currentIndex] === option && !isQuizOptionCorrect(option),
                                                    'opacity-60 cursor-not-allowed bg-gray-50 border-gray-200': quiz.answers[quiz.currentIndex] && quiz.answers[quiz.currentIndex] !== option && !isQuizOptionCorrect(option)
                                                 }">
                                                
                                                <div class="mr-4 font-medium min-w-[24px]" 
                                                     :class="{
                                                        'text-gray-500': !quiz.answers[quiz.currentIndex] || (quiz.answers[quiz.currentIndex] !== option && !isQuizOptionCorrect(option)),
                                                        'text-green-800': quiz.answers[quiz.currentIndex] && isQuizOptionCorrect(option),
                                                        'text-red-800': quiz.answers[quiz.currentIndex] && quiz.answers[quiz.currentIndex] === option && !isQuizOptionCorrect(option)
                                                     }"
                                                     x-text="String.fromCharCode(65 + index) + '.'"></div>
                                                
                                                <div class="flex-grow text-base" x-text="option"></div>

                                                <!-- Icons for feedback -->
                                                <template x-if="quiz.answers[quiz.currentIndex] && (quiz.answers[quiz.currentIndex] === option || isQuizOptionCorrect(option))">
                                                    <div class="absolute right-4 top-1/2 -translate-y-1/2">
                                                        <i class="fas fa-check-circle text-green-500 text-xl" x-show="isQuizOptionCorrect(option)"></i>
                                                        <i class="fas fa-times-circle text-red-500 text-xl" x-show="!isQuizOptionCorrect(option) && quiz.answers[quiz.currentIndex] === option"></i>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex items-center justify-between mt-8">
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="flex items-center text-gray-700 font-medium hover:text-gray-900 focus:outline-none">
                                                Hint <i class="fas fa-chevron-down ml-2 text-xs transition-transform" :class="{'transform rotate-180': open}"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-4 z-10 text-sm text-gray-600">
                                                No hints available for this question.
                                            </div>
                                        </div>

                                        <div class="flex space-x-4">
                                            <button @click="quizPrevQuestion()" 
                                                    class="px-6 py-2 rounded-full border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                    :disabled="quiz.currentIndex === 0">
                                                Previous
                                            </button>
                                            <button @click="quizNextQuestion()" 
                                                    class="px-8 py-2 rounded-full bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                                    :disabled="quiz.currentIndex === quiz.questions.length - 1">
                                                Next
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="currentVideo.contentType === 'text' && currentVideo.content">
                                <div x-html="currentVideo.content"></div>
                            </template>

                            <template x-if="!currentVideo.contentType || (currentVideo.contentType === 'text' && !currentVideo.content)">
                                <div class="p-8">
                                    <p class="text-gray-500">Content not found.</p>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!currentVideo">
                        <div class="flex items-center justify-center h-64">
                            <p class="text-gray-500">Select a video to start learning</p>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-center justify-between p-4 border-t border-gray-200 bg-white md:sticky md:bottom-0 z-40">
                <div class="flex items-center space-x-4 mb-4 md:mb-0">
                    <button id="prev-btn" @click="previousVideo()" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold transition-colors duration-200" :disabled="!hasPreviousVideo()" :class="{'opacity-50 cursor-not-allowed': !hasPreviousVideo()}">
                        <i class="fas fa-arrow-left mr-2"></i>Previous
                    </button>
                    <button id="next-btn" @click="nextVideo()" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-colors duration-200" :disabled="!hasNextVideo()" :class="{'opacity-50 cursor-not-allowed': !hasNextVideo()}">
                        Next<i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>

                <div class="flex items-center space-x-2 md:space-x-4">
                    <button id="complete-btn" @click="toggleComplete()" class="px-4 py-2 rounded-lg border font-semibold transition-colors duration-200" :class="currentVideo?.isCompleted ? 
                              'bg-green-100 text-green-700 border-green-500' : 
                              'border-green-500 text-green-600 hover:bg-green-50'">
                        <i class="fas mr-2" :class="currentVideo?.isCompleted ? 'fa-check-circle' : 'fa-check-circle'"></i>
                        <span x-text="currentVideo?.isCompleted ? 'Completed' : 'Mark as Completed'"></span>
                    </button>

                    <button id="bookmark-btn" @click="toggleBookmark(currentVideo)" class="px-4 py-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors duration-200">
                        <i class="far fa-bookmark mr-2"></i>Bookmark
                    </button>

                    <a id="download-btn" :href="currentVideo?.resourceUrl || '#'" class="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-900 text-white font-semibold transition-colors duration-200" :class="{'hidden': !currentVideo?.hasDownload}">
                        <i class="fas fa-download mr-2"></i>Download Resource
                    </a>
                </div>
            </div>
        </main>
    </div>

    <?php wp_footer(); ?>
</body>

</html>
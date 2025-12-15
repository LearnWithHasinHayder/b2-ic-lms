// // Passive event listener support to prevent scroll-blocking warnings
// (function() {
//   let supportsPassive = false;
//   try {
//     const opts = Object.defineProperty({}, 'passive', {
//       get: function() {
//         supportsPassive = true;
//       }
//     });
//     window.addEventListener('testPassive', null, opts);
//     window.removeEventListener('testPassive', null, opts);
//   } catch (e) {}

//   // Override addEventListener to make touch and wheel events passive by default
//   if (supportsPassive) {
//     const originalAddEventListener = EventTarget.prototype.addEventListener;
//     EventTarget.prototype.addEventListener = function(type, listener, options) {
//       const usesPassive = ['touchstart', 'touchmove', 'wheel', 'mousewheel'].includes(type);
//       if (usesPassive && typeof options !== 'object') {
//         options = { passive: true };
//       } else if (usesPassive && typeof options === 'object' && !('passive' in options)) {
//         options.passive = true;
//       }
//       return originalAddEventListener.call(this, type, listener, options);
//     };
//   }
// })();

// Make coursePlayer function globally available
function coursePlayer() {
  return {
    courseData: null,
    currentVideo: null,
    sidebarOpen: false,
    isDark: false,
    currentVideoIndex: 0,
    allVideos: [],
    
    // Quiz specific state
    quiz: {
      questions: [],
      currentIndex: 0,
      answers: {},
      isCompleted: false
    },
    
    init() {
      this.loadCourseData();
      this.initializeTheme();
      this.setupResizeHandler();
    },
    
    async loadCourseData() {
      try {
        // Use the API URL passed from WordPress if available, otherwise fallback to course.json
        const apiUrl = icLmsConfig.apiUrl;
        const response = await fetch(apiUrl);
        this.courseData = await response.json();
        
        // Flatten all videos for navigation
        this.allVideos = [];
        this.courseData.course.chapters.forEach(chapter => {
          chapter.videos.forEach(video => {
            this.allVideos.push(video);
          });
        });
        
        // Initialize chapter states
        this.courseData.course.chapters.forEach(chapter => {
          chapter.isOpen = chapter.id === 1; // Open first chapter by default
        });
        
        // Try to restore last watched episode for this course
        const lastWatchedVideo = this.getLastWatchedEpisode();
        
        if (lastWatchedVideo) {
          // Find the video in allVideos array
          const videoToSelect = this.allVideos.find(v => 
            v.id === lastWatchedVideo.episodeId && 
            this.findChapterForVideo(v)?.id === lastWatchedVideo.chapterId
          );
          
          if (videoToSelect) {
            this.selectVideo(videoToSelect);
          } else {
            // Fallback to first video if saved video not found
            if (this.allVideos.length > 0) {
              this.selectVideo(this.allVideos[0]);
            }
          }
        } else {
          // Select first video if no saved progress
          if (this.allVideos.length > 0) {
            this.selectVideo(this.allVideos[0]);
          }
        }
        
        // Apply syntax highlighting after content is loaded
        this.$nextTick(() => {
          if (typeof hljs !== 'undefined') {
            hljs.highlightAll();
          }
        });
        
        // Load user bookmarks
        await this.loadBookmarks();
        
        // Load user completed videos
        await this.loadCompletedVideos();
        
      } catch (error) {
        console.error('Failed to load course data:', error);
      }
    },
    
    async loadBookmarks() {
      try {
        // Extract base URL from course API URL
        const baseUrl = icLmsConfig.apiUrl.split('/course/')[0];
        // Fetch user bookmarks for current course with authentication nonce
        const response = await fetch(`${baseUrl}/user/bookmarks?course_id=${this.courseData.course.id}`, {
          headers: {
            'X-WP-Nonce': icLmsConfig.restNonce,
          }
        });
        // Parse JSON response
        const data = await response.json();
        // Check if request was successful and bookmarks exist
        if (data.success && data.bookmarks) {
          // Loop through each chapter's bookmarked episodes
          for (const [chapterId, episodeIds] of Object.entries(data.bookmarks)) {
            // Find the chapter object by ID
            const chapter = this.courseData.course.chapters.find(ch => ch.id == chapterId);
            if (chapter) {
              // Loop through episode IDs in this chapter
              episodeIds.forEach(epId => {
                // Find the video object by ID
                const video = chapter.videos.find(v => v.id == epId);
                if (video) {
                  // Mark this video as bookmarked
                  video.isBookmarked = true;
                }
              });
            }
          }
        }
      } catch (error) {
        // Log any errors that occur during bookmark loading
        console.error('Failed to load bookmarks:', error);
      }
    },
    
    async loadCompletedVideos() {
      try {
        // Extract base URL from course API URL
        const baseUrl = icLmsConfig.apiUrl.split('/course/')[0];
        // Fetch user completed episodes for current course with authentication nonce
        const response = await fetch(`${baseUrl}/user/completes?course_id=${this.courseData.course.id}`, {
          headers: {
            'X-WP-Nonce': icLmsConfig.restNonce,
          }
        });
        // Parse JSON response
        const data = await response.json();
        // Check if request was successful and completed episodes exist
        if (data.success && data.completed) {
          // Loop through each chapter's completed episodes
          for (const [chapterId, episodeIds] of Object.entries(data.completed)) {
            // Find the chapter object by ID
            const chapter = this.courseData.course.chapters.find(ch => ch.id == chapterId);
            if (chapter) {
              // Loop through episode IDs in this chapter
              episodeIds.forEach(epId => {
                // Find the video object by ID
                const video = chapter.videos.find(v => v.id == epId);
                if (video) {
                  // Mark this video as completed
                  video.isCompleted = true;
                }
              });
            }
          }
        }
      } catch (error) {
        // Log any errors that occur during completed videos loading
        console.error('Failed to load completed videos:', error);
      }
    },
    
    selectVideo(video) {
      this.currentVideo = video;
      this.currentVideoIndex = this.allVideos.findIndex(v => v.id === video.id);
      
      // Initialize quiz if content type is quiz
      if (video.contentType === 'quiz' && video.content) {
        this.initQuiz(video.content);
      } else {
        // Reset quiz state when switching to non-quiz content
        this.quiz = {
            questions: [],
            currentIndex: 0,
            answers: {},
            isCompleted: false
        };
      }
      
      // Save this as the last watched episode
      this.saveLastWatchedEpisode(video);
      
      // Open the chapter containing this video
      this.courseData.course.chapters.forEach(chapter => {
        chapter.isOpen = chapter.videos.some(v => v.id === video.id);
      });
      
      // Close sidebar on mobile
      if (this.isMobile()) {
        this.sidebarOpen = false;
      }
      
      // Apply syntax highlighting for new content
      this.$nextTick(() => {
        if (typeof hljs !== 'undefined') {
          hljs.highlightAll();
        }
      });
    },
    
    // Helper method to find which chapter a video belongs to
    findChapterForVideo(video) {
      return this.courseData.course.chapters.find(chapter => 
        chapter.videos.some(v => v.id === video.id)
      );
    },
    
    // Save last watched episode to localStorage
    saveLastWatchedEpisode(video) {
      const courseId = this.courseData.course.id;
      const chapter = this.findChapterForVideo(video);
      
      if (courseId && chapter) {
        const progressData = {
          courseId: courseId,
          chapterId: chapter.id,
          episodeId: video.id,
          episodeTitle: video.title,
          timestamp: new Date().toISOString()
        };
        
        localStorage.setItem(`ic_lms_progress_${courseId}`, JSON.stringify(progressData));
      }
    },
    
    // Get last watched episode from localStorage
    getLastWatchedEpisode() {
      const courseId = this.courseData.course.id;
      
      if (!courseId) return null;
      
      const savedData = localStorage.getItem(`ic_lms_progress_${courseId}`);
      
      if (savedData) {
        try {
          return JSON.parse(savedData);
        } catch (e) {
          console.error('Failed to parse saved progress:', e);
          return null;
        }
      }
      
      return null;
    },
    
    toggleChapter(chapter) {
      // Close all chapters, then open selected one (accordion behavior)
      this.courseData.course.chapters.forEach(ch => {
        ch.isOpen = ch.id === chapter.id ? !ch.isOpen : false;
      });
    },
    
    async toggleBookmark(video) {
      // Return early if no video provided
      if (!video) return;
      // Find the chapter containing this video
      const chapter = this.findChapterForVideo(video);
      // Return early if chapter not found
      if (!chapter) return;
      // Determine HTTP method: DELETE if bookmarked, POST if not
      const method = video.isBookmarked ? 'DELETE' : 'POST';
      try {
        // Extract base URL from course API URL
        const baseUrl = icLmsConfig.apiUrl.split('/course/')[0];
        // Send API request to toggle bookmark
        const response = await fetch(`${baseUrl}/user/bookmark`, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': icLmsConfig.restNonce,
          },
          body: JSON.stringify({
            course_id: this.courseData.course.id,
            chapter_id: chapter.id,
            episode_id: video.id
          })
        });
        // Parse JSON response
        const data = await response.json();
        if (data.success) {
          // Update local bookmark state from server response
          video.isBookmarked = data.is_bookmarked;
        } else {
          // Log API error
          console.error('Bookmark action failed:', data);
        }
      } catch (error) {
        // Log network or other errors
        console.error('Error handling bookmark:', error);
      }
    },
    
    getYouTubeEmbedUrl(url) {
      // Check if it's a YouTube URL
      if (url && (url.includes('youtube.com') || url.includes('youtu.be'))) {
        // Add YouTube API parameters if not already present
        const separator = url.includes('?') ? '&' : '?';
        return url + separator + 'enablejsapi=1&origin=' + window.location.origin;
      }
      // Return original URL if not YouTube
      return url;
    },
    
    async toggleComplete() {
      // Return early if no current video
      if (!this.currentVideo) return;
      
      // Find the chapter containing this video
      const chapter = this.findChapterForVideo(this.currentVideo);
      if (!chapter) return;
      
      // Determine HTTP method: DELETE if completed, POST if not
      const method = this.currentVideo.isCompleted ? 'DELETE' : 'POST';
      
      try {
        // Extract base URL from course API URL
        const baseUrl = icLmsConfig.apiUrl.split('/course/')[0];
        // Send API request to toggle completion
        const response = await fetch(`${baseUrl}/user/complete`, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': icLmsConfig.restNonce,
          },
          body: JSON.stringify({
            course_id: this.courseData.course.id,
            chapter_id: chapter.id,
            episode_id: this.currentVideo.id
          })
        });
        // Parse JSON response
        const data = await response.json();
        if (data.success) {
          // Update local completion state from server response
          this.currentVideo.isCompleted = data.is_completed;
        } else {
          // Log API error
          console.error('Mark complete toggle failed:', data);
        }
      } catch (error) {
        // Log network or other errors
        console.error('Error toggling completion:', error);
      }
    },
    
    previousVideo() {
      if (this.hasPreviousVideo()) {
        this.currentVideoIndex--;
        this.selectVideo(this.allVideos[this.currentVideoIndex]);
      }
    },
    
    nextVideo() {
      if (this.hasNextVideo()) {
        this.currentVideoIndex++;
        this.selectVideo(this.allVideos[this.currentVideoIndex]);
      }
    },
    
    hasPreviousVideo() {
      return this.currentVideoIndex > 0;
    },
    
    hasNextVideo() {
      return this.currentVideoIndex < this.allVideos.length - 1;
    },
    
    get progressPercentage() {
      if (this.allVideos.length === 0) return 0;
      const completedVideos = this.allVideos.filter(v => v.isCompleted).length;
      return Math.round((completedVideos / this.allVideos.length) * 100);
    },
    
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },
    
    closeSidebar() {
      this.sidebarOpen = false;
    },
    
    isMobile() {
      return window.innerWidth <= 768;
    },
    
    initializeTheme() {
      const stored = localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.isDark = stored ? stored === 'dark' : prefersDark;
      this.updateTheme();
    },
    
    toggleTheme() {
      this.isDark = !this.isDark;
      this.updateTheme();
    },
    
    updateTheme() {
      if (this.isDark) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
      localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
    },
    
    setupResizeHandler() {
      window.addEventListener('resize', () => {
        // Alpine.js will automatically re-evaluate isMobile() when needed
      }, { passive: true });
    },

    // Quiz Methods
    initQuiz(content) {
      try {
        const questions = typeof content === 'string' ? JSON.parse(content) : content;
        
        if (Array.isArray(questions)) {
          this.quiz = {
            questions: questions,
            currentIndex: 0,
            answers: {},
            isCompleted: false
          };
        } else {
          console.error("Quiz content is not an array");
          this.quiz.questions = [];
        }
      } catch (e) {
        console.error("Failed to parse quiz JSON", e);
        this.quiz.questions = [];
      }
    },

    get currentQuizQuestion() {
      if (!this.quiz.questions || this.quiz.questions.length === 0) return null;
      return this.quiz.questions[this.quiz.currentIndex];
    },

    isQuizOptionSelected(option) {
      if (!this.quiz.answers) return false;
      return this.quiz.answers[this.quiz.currentIndex] === option;
    },

    isQuizOptionCorrect(option) {
      const question = this.currentQuizQuestion;
      if (!question) return false;
      return question.answer === option;
    },

    handleQuizOptionClick(option) {
      // Prevent changing answer if already selected
      if (this.quiz.answers[this.quiz.currentIndex]) return;
      
      this.quiz.answers[this.quiz.currentIndex] = option;
    },

    quizNextQuestion() {
      if (this.quiz.currentIndex < this.quiz.questions.length - 1) {
        this.quiz.currentIndex++;
      }
    },

    quizPrevQuestion() {
      if (this.quiz.currentIndex > 0) {
        this.quiz.currentIndex--;
      }
    }
  }
}
url: 'api/question-api.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    question_id: questionId,
                    csrf_token: $('#question-form input[name="csrf_token"]').val()
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        // Show success message
                        swalCustom.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: 'ลบคำถามเรียบร้อยแล้ว'
                        });
                        
                        // Reload questions
                        loadQuestions();
                        
                        // Reload topics (to update question counts)
                        loadTopics();
                        
                        // If current question is deleted, reset form
                        if (currentQuestionId == questionId) {
                            resetQuestionForm();
                            setupNewQuestion();
                        }
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถลบคำถามได้'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // Show image preview in modal
        function showImagePreview(src) {
            $('#preview-image-large').attr('src', src);
            $('#imagePreviewModal').modal('show');
        }
        
        // Reset modals when closed
        $('#addTopicModal').on('hidden.bs.modal', function() {
            $('#addTopicForm')[0].reset();
            $('#addTopicForm .is-invalid').removeClass('is-invalid');
            $('#addTopicForm input[name="exam_set_id"]').val(<?= $examSetId ?>);
        });
        
        $('#editTopicModal').on('hidden.bs.modal', function() {
            $('#editTopicForm .is-invalid').removeClass('is-invalid');
        });
    });
    </script>
  </body>
</html>
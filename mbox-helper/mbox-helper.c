/********************************************************************************
* mbox-helper/mbox-helper.c : read and parse an mbox file
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

#define _GNU_SOURCE
#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <locale.h>
#include <stdbool.h>
#include <getopt.h>

/** Macros
 */
#define LTRIM(pos)              while (isspace(*pos)) { pos++; }
#define STRTOLOWER(str, ptr)    for (ptr = str ; *ptr ; ptr++) { *ptr = tolower(*ptr); }

/** MBox pointer
 */
typedef struct
{
    FILE *fp;                   // File pointer
    long int lastLine;          // Offset of the precedent line (-1 if invalid)
    long int currentLine;       // Offset of the current line
    long int messageId;         // Current message Id
    long int messageBeginning;  // Offset of the beginning of the message (FROM_ line)

    char *line;                 // Line buffer
    bool isFrom_;               // Is the current line a From_ line ?
}
MBox;

/** Open a mbox
 * @param filename char* Path to the file to open
 * @return NULL on error, a well initialized MBox structure pointer on success
 */
static MBox *openMBox(char *filename)
{
    FILE *fp;
    MBox *mbox;

    fp = fopen(filename, "r"); 
    if (!fp) {
        return NULL;
    }

    mbox = (MBox*)malloc(sizeof(MBox));
    mbox->fp               = fp;
    mbox->lastLine         = -1;
    mbox->currentLine      = 0;
    mbox->messageId        = 0;
    mbox->messageBeginning = 0;
    mbox->line             = NULL;
    mbox->isFrom_          = false;
    return mbox;
}

/** Close a mbox
 */
static void closeMBox(MBox *mbox)
{
    if (!mbox) {
        return;
    }
    fclose(mbox->fp);
    if (mbox->line) {
        free(mbox->line);
    }
    free(mbox);
}

/** Read a line in a file
 * @param mbox MBox the source mbox
 * @return the read line
 *
 * This function do not only read the line, it does minimum parsing stuff and
 * set the corresponding mbox structure flags
 */
static char *readLine(MBox *mbox)
{
    int length;
    mbox->lastLine    = mbox->currentLine;
    mbox->currentLine = ftell(mbox->fp);
    mbox->isFrom_ = false;
    
    if (!mbox->line) {
        mbox->line = (char*)malloc(1001);
    }
    if (!fgets(mbox->line, 1000, mbox->fp)) {
        mbox->currentLine = -1;
        return NULL;
    }
    length = ftell(mbox->fp) - mbox->currentLine;
    if (length > 1000) {
        length = 1000;
    }
    if (length) {
        while (length >= 0 && (isspace(mbox->line[length]) || mbox->line[length] == '\0')) {
            length--;
        }
        mbox->line[length + 1] = '\0';
    }
    mbox->isFrom_     = (strstr(mbox->line, "From ") == mbox->line);
    if (mbox->isFrom_ && mbox->messageBeginning != mbox->currentLine) {
        mbox->messageBeginning = mbox->currentLine;
        mbox->messageId++;
    }
    return mbox->line;
}

/** Read a From_ line from the mbox
 * the From_ line MUST be the current or the next one
 */
static bool readFrom_(MBox *mbox)
{
    if (!mbox->isFrom_) {
        readLine(mbox);
    }
    return !!mbox->isFrom_;
}

/** Read a message
 * The message is not stored or returned, just skipped.
 * If display is true, the message is printed on stdio
 */
static void readMessage(MBox *mbox, bool display)
{
    if (!readFrom_(mbox)) {
        return;
    }
    while (readLine(mbox)) {
        if (mbox->isFrom_) {
            return;
        }
        if (display) {
            if (strstr(mbox->line, ">From ") == mbox->line) {
                puts(mbox->line + 1);
            } else {
                puts(mbox->line);
            }
        }
    }
}

/** Read the headers of a message
 * Read the given headers of the current message of the given mbox
 * @param mbox     MBox    source
 * @param headers  char**  list of requested headers
 * @param hdrsize  int     size of @ref headers
 *
 * THe headers are printed on stdio
 */
static void readHeaders(MBox *mbox, char **headers, int hdrsize)
{
    char *current = NULL;
    char *pos, *ptr;
    int size, i;
    
    if (!readFrom_(mbox)) {
        return;
    }
    printf("%d\n%d\n", (int)mbox->messageId, (int)mbox->messageBeginning);
    while (readLine(mbox)) {
        if (mbox->isFrom_ || !strlen(mbox->line)) {
            break;
        }
        if (current && strlen(mbox->line) && isspace(*(mbox->line))) {
            pos = mbox->line;
            LTRIM(pos);
            printf(" %s", pos);
        } else {
            if (current) {
                printf("\n");
                free(current);
                current = NULL;
            }
            pos = strchr(mbox->line, ':');
            if (!pos || pos == mbox->line) {
                continue;
            }
            size = pos - mbox->line;
            for (i = 0 ; i < hdrsize ; i++) {
                if ((int)strlen(headers[i]) == size && strcasestr(mbox->line, headers[i]) == mbox->line) {
                    current = (char*)malloc(size + 1);
                    strcpy(current, headers[i]);
                    current[size] = '\0'; 
                }
            }
            if (!current && !hdrsize) {
                current = (char*)malloc(size + 1);
                strncpy(current, mbox->line, size);
                current[size] = '\0';
                STRTOLOWER(current, ptr);
            }
            if (current) {
                puts(current);
                pos++;
                LTRIM(pos);
                printf("%s", pos);
            }
        }
    }
    if (current) {
        printf("\n");
        free(current);
        current = NULL;
    }
    printf("\n");
}

/** Go back to the beginning of the file
 */
static void rewindMBox(MBox *mbox)
{
    fseek(mbox->fp, 0, SEEK_SET);
    mbox->messageId = 0;
    mbox->messageBeginning = 0;
    readLine(mbox);
}

/** Go back to the beginning of the current message
 * @return true if the beginning of a message has been reached
 */
static bool rewindMessage(MBox *mbox)
{
    if (mbox->isFrom_) {
        return true;
    }
    fseek(mbox->fp, mbox->messageBeginning, SEEK_SET);
    mbox->currentLine = -1;
    mbox->lastLine = -1;
    readLine(mbox);
    return mbox->isFrom_;
}

/** Move to the given offset
 * @param mbox   MBox the source mbox
 * @param offset int  offset where to go
 * @param idx    int  index of the message corresponding with the offset
 * @return true if the given offset is the beginning of a message
 */
static bool goToOffset(MBox *mbox, int offset, int idx)
{
    fseek(mbox->fp, offset, SEEK_SET);
    mbox->currentLine = -1;
    mbox->lastLine    = -1;
    mbox->messageBeginning = offset;
    mbox->messageId   = idx;
    readLine(mbox);
    if (!mbox->isFrom_) {
        return false;
    }
    return true;
}

/** Move to the given message number
 * @param mbox MBox the source mbox
 * @param idx  int  the index of the message where to go
 * @return true if the given message has been reached
 */
static bool goToMessage(MBox *mbox, int idx)
{
    if (mbox->messageId > idx) {
        rewindMBox(mbox); 
    } else if(mbox->messageId == idx) {
        rewindMessage(mbox);
        return true;
    } else if (!mbox->isFrom_) {
        while (!feof(mbox->fp) && !mbox->isFrom_) {
            readLine(mbox);
        }
        if (feof(mbox->fp)) {
            return false;
        }
    }
    while (mbox->messageId < idx && !feof(mbox->fp)) {
        readMessage(mbox, false);
    }
    if (mbox->messageId == idx) {
        return true;
    }
    return false;
}


/** Display the program usage help
 */
static void help(void)
{
    printf("Usage: mbox-helper [action] [options] -f filename [header1 [header2 ...]]\n"
           "Actions: only the last action given is applied\n"
           "    -c               compute the number of messages. If -p is given, process the file starting à the given offset\n"
           "    -d               return the headers of the messages given with the -m option. If no header is given in the\n"
           "                     command line options, all the headers are returned. The headers are return with the format:\n"
           "                        MSG1_ID\\n\n"
           "                        MSG1_OFFSET\\n\n"
           "                        MSG1_HEADER1_NAME\\n\n"
           "                        MSG1_HEADER1_VALUE\\n\n"
           "                        MSG1_HEADER2_NAME\\n\n"
           "                        MSG2_HEADER2_VALUE\\n\n"
           "                        ...\n"
           "                     Messages are separated by a blank line\n"
           "    -b               return the body of the message given by -m (only 1 message is returned)\n"
           "Options:\n"
           "    -m begin[:end]   id or range of messages to process\n"
           "    -p id:pos        indicate that message `id` begins at offset `pos`\n"
           "    -h               print this help\n");
}

/** Display an error message
 * This function display the giver error, then show the program help and exit the program
 * The memory must be cleared before calling this function
 */
static void error(const char *message)
{
    fprintf(stderr, "Invalid parameters: %s\n", message);
    help();
    exit(1);
}

/** Main function
 */
int main(int argc, char *argv[])
{
    int c, i = 0;
    int fmid = -1, lmid = -1;
    int pmid = 0, pos = 0;
    char *filename = NULL;
    char **headers = NULL;
    char action = 0;
    int headerNb   = 0;
    char *endptr;
    MBox *mbox;

    // Parse command line
    while ((c = getopt(argc, argv, ":bcdp:hm:f:")) != -1) {
        switch (c) {
          case 'f':
            filename = optarg;
            break;
          case 'm':
            fmid = strtol(optarg, &endptr, 10);
            if (endptr == optarg) {
                error("invalid message id");
            }
            if (*endptr != ':' || !*(endptr+1)) {
                lmid = fmid;
            } else {
                lmid = atoi(endptr + 1);
            }
            break;
          case 'p':
            if ((endptr = strchr(optarg, ':')) != NULL) {
                pmid = strtol(optarg, &endptr, 10);
                if (*endptr != ':' || !*(endptr+1)) {
                   error("invalid position couple given");
                }
                pos = atoi(endptr + 1);
            } else {
                error("invalid position given");
            }
            break;
          case 'c': case 'd': case 'b':
            action = c;
            break;
          case 'h':
            help();
            return 0;
          case ':':
            fprintf(stderr, "Missing argument to -%c\n", optopt);
            break;
          case '?':
            fprintf(stderr, "Unrecognized option: -%c\n", optopt);
            break;
        }
    }

    // Check command line arguments consistence
    if (!filename) {
        error("no file defined");
    }

    setlocale(LC_ALL, "C");
  
    headerNb = argc - optind;
    headers  = (argv + optind);
    for (i = 0 ; i < headerNb ; i++) {
        STRTOLOWER(headers[i], endptr);
    }

    mbox = openMBox(filename);
    if (!mbox) {
        fprintf(stderr, "can't open file '%s'\n", filename);
        return 1;
    }
    if ((fmid >= pmid || fmid == -1) && pos) {
        if (!goToOffset(mbox, pos, pmid)) {
            fprintf(stderr, "Offset %d do not match with a message beginning\n", pos);
            rewindMBox(mbox);
        }   
    }

    // Do requested stuff
    switch (action) {
      case 'b':
        if (fmid == -1) {
            fprintf(stderr, "you have to define a message number\n");
            break;
        }
        goToMessage(mbox, fmid);
        readMessage(mbox, true);
        break;
      case 'c':
        while (!feof(mbox->fp)) {
            readLine(mbox);
        }
        printf("%d\n", (int)(mbox->messageId + 1));
        break;
      case 'd':
        if (fmid == -1) {
             fprintf(stderr, "you have to define a message number\n");
             break;
        }
        for (i = fmid ; i <= lmid ; i++) {
            goToMessage(mbox, i);
            readHeaders(mbox, headers, headerNb);
        }
        break;
    }
    closeMBox(mbox);

    return 0;
}

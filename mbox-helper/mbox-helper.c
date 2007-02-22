/** Read an mbox
 */

#define _GNU_SOURCE
#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <locale.h>

/** Macros
 */
#define LTRIM(pos)              while (isspace(*pos)) { pos++; }
#define STRTOLOWER(str, ptr)    for (ptr = str ; *ptr ; ptr++) { *ptr = tolower(*ptr); }

/** Boolean
 */
typedef char bool;
#define TRUE ((bool)(-1))
#define FALSE ((bool)(0))

/** MBox pointer
 */
typedef struct
{
    FILE *fp; // File pointer
    long int lastLine;          // Offset of the precedent line (-1 if invalid)
    long int currentLine;       // Offset of the current line
    long int messageId;         // Current message Id
    long int messageBeginning;  // Offset of the beginning of the message (FROM_ line)

    char *line;                 // Line buffer
    bool isFrom_;               // Is the current line a From_ line ?
}
MBox;

/** Open a mbox
 */
MBox *openMBox(char *filename)
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
    mbox->isFrom_          = FALSE;
    return mbox;
}

/** Close a mbox
 */
void closeMBox(MBox *mbox)
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
 */
char *readLine(MBox *mbox)
{
    int length;
    mbox->lastLine    = mbox->currentLine;
    mbox->currentLine = ftell(mbox->fp);
    mbox->isFrom_ = FALSE;
    
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

/** Return to the last line
 */
bool lastLine(MBox *mbox)
{
    if (mbox->lastLine != -1) {
        fseek(mbox->fp, mbox->lastLine, SEEK_SET);
        mbox->lastLine = -1;
        readLine(mbox);
        return TRUE;
    }
    return FALSE;
}

bool readFrom_(MBox *mbox)
{
    if (!mbox->isFrom_) {
        readLine(mbox);
    }
    if (!mbox->isFrom_) {
        return FALSE;
    }
    return TRUE; 
}

/** Read a message
 */
void readMessage(MBox *mbox, bool display)
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
 */
void readHeaders(MBox *mbox, char **headers, int hdrsize)
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
                if (strlen(headers[i]) == size && strcasestr(mbox->line, headers[i]) == mbox->line) {
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
void rewindMBox(MBox *mbox)
{
    fseek(mbox->fp, 0, SEEK_SET);
    mbox->messageId = 0;
    mbox->messageBeginning = 0;
    readLine(mbox);
}

/** Go back to the beginning of the message
 */
bool rewindMessage(MBox *mbox)
{
    if (mbox->isFrom_) {
        return TRUE;
    }
    fseek(mbox->fp, mbox->messageBeginning, SEEK_SET);
    mbox->currentLine = -1;
    mbox->lastLine = -1;
    readLine(mbox);
    return mbox->isFrom_;
}

/** Move to the given offset
 */
bool goToOffset(MBox *mbox, int offset, int index)
{
    fseek(mbox->fp, offset, SEEK_SET);
    mbox->currentLine = -1;
    mbox->lastLine    = -1;
    mbox->messageBeginning = offset;
    mbox->messageId   = index;
    readLine(mbox);
    if (!mbox->isFrom_) {
        return FALSE;
    }
    return TRUE;
}

/** Move to the given message number
 */
bool goToMessage(MBox *mbox, int index)
{
    if (mbox->messageId > index) {
        rewindMBox(mbox); 
    } else if(mbox->messageId == index) {
        rewindMessage(mbox);
        return TRUE;
    } else if (!mbox->isFrom_) {
        while (!feof(mbox->fp) && !mbox->isFrom_) {
            readLine(mbox);
        }
        if (feof(mbox->fp)) {
            return FALSE;
        }
    }
    while (mbox->messageId < index && !feof(mbox->fp)) {
        readMessage(mbox, FALSE);
    }
    if (mbox->messageId == index) {
        return TRUE;
    }
    return FALSE;
}


/** Display the program help
 */
void help(void)
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
 */
void error(char *message)
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
    
    /* getopt variables */
    extern char *optarg;
    extern int optind, optopt;

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
            if (*endptr != ':') {
                lmid = fmid;
            } else {
                lmid = atoi(endptr + 1);
            }
            break;
          case 'p':
            if ((endptr = strchr(optarg, ':')) != NULL) {
                pmid = strtol(optarg, &endptr, 10);
                if (*endptr != ':') {
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
        fprintf(stderr, "can't open file '%s'", filename);
    }
    if ((fmid >= pmid || fmid == -1) && pos) {
        if (!goToOffset(mbox, pos, pmid)) {
            fprintf(stderr, "Offset %d do not match with a message beginning\n", pos);
            rewindMBox(mbox);
        }   
    }
    switch (action) {
      case 'b':
        if (fmid == -1) {
            fprintf(stderr, "you have to define a message number");
            break;
        }
        goToMessage(mbox, fmid);
        readMessage(mbox, TRUE);
        break;
      case 'c':
        while (!feof(mbox->fp)) {
            readLine(mbox);
        }
        printf("%d\n", (int)(mbox->messageId + 1));
        break;
      case 'd':
        if (fmid == -1) {
             fprintf(stderr, "you have to define a message number");
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
